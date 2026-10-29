<?php

namespace App\Services;

use App\Http\Requests\QuoteRequest;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

// Batches for «اكتشف». Three tabs, all paged by cursor, never by offset:
// - «الأحدث» walks the (created_at, id) index with a keyset cursor;
// - «مختارة لك» and «الأشهر» rank the candidate pool once, freeze the order for this reader (snapshot_minutes),
//   and the cursor points into that frozen list, so new likes or quotes never repeat or skip one mid-scroll.
class DiscoverFeed
{
    public const TABS = ['for-you', 'latest', 'popular'];
    public const CANDIDATES_CACHE = 'midad.discover.candidates';
    private const SEEN_SESSION = 'discover.seen';

    public function __construct(private FeedRanker $ranker) {}

    /**
     * @return array{quotes: Collection<int, Quote>, next: ?string}
     * @throws InvalidArgumentException when the cursor is not one this feed issued
     */
    public function page(?User $user, string $tab, string $category, ?string $cursor, ?int $pin = null): array
    {
        $page = $tab === 'latest'
            ? $this->latest($category, $cursor, $pin)
            : $this->ranked($user, $tab, $category, $cursor, $pin);
        $this->rememberSeen($page['quotes']->modelKeys());

        return $page;
    }

    /** Categories that have quotes, in the library's order (no extra query: read from the cached pool). */
    public function categories(): array
    {
        $present = array_flip(array_column($this->candidates(), 'category'));

        return array_values(array_filter(QuoteRequest::CATEGORIES, fn (string $category) => isset($present[$category])));
    }

    private function latest(string $category, ?string $cursor, ?int $pin): array
    {
        $decoded = null;
        if ($cursor !== null && ! ($decoded = Cursor::fromEncoded($cursor))) {
            throw new InvalidArgumentException('Unknown cursor.');
        }

        $page = Quote::query()->withCardData()
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->cursorPaginate(config('feed.batch_size'), ['*'], 'cursor', $decoded);
        $quotes = $page->getCollection();

        if ($pin && $cursor === null && $pinned = Quote::query()->withCardData()->find($pin)) {
            $quotes = $quotes->reject(fn (Quote $quote) => $quote->id === $pinned->id)->prepend($pinned)->values();
        }

        return ['quotes' => $quotes, 'next' => $page->nextCursor()?->encode()];
    }

    private function ranked(?User $user, string $tab, string $category, ?string $cursor, ?int $pin): array
    {
        $snapshot = null;
        $position = 0;
        if ($cursor !== null) {
            $parts = json_decode((string) base64_decode(strtr($cursor, '-_', '+/'), true), true);
            if (! is_array($parts) || ! is_string($parts['t'] ?? null) || ! is_int($parts['p'] ?? null) || $parts['p'] < 0) {
                throw new InvalidArgumentException('Unknown cursor.');
            }
            $stored = Cache::get($this->snapshotKey($parts['t']));
            // An expired feed starts over; quotes this session already saw move to the end, so they do not come straight back.
            if (is_array($stored) && hash_equals($stored['owner'], $this->owner($user))) {
                $snapshot = $stored + ['token' => $parts['t']];
                $position = $parts['p'];
            }
        }
        $snapshot ??= $this->startSnapshot($user, $tab, $category, $cursor === null ? $pin : null);

        $ids = array_slice($snapshot['ids'], $position, config('feed.batch_size'));
        $place = array_flip($ids);
        $quotes = Quote::query()->withCardData()->whereKey($ids)->get()
            ->sortBy(fn (Quote $quote) => $place[$quote->id])->values();
        $nextPosition = $position + count($ids);
        $next = $nextPosition < count($snapshot['ids'])
            ? rtrim(strtr(base64_encode(json_encode(['t' => $snapshot['token'], 'p' => $nextPosition])), '+/', '-_'), '=')
            : null;

        return ['quotes' => $quotes, 'next' => $next];
    }

    private function startSnapshot(?User $user, string $tab, string $category, ?int $pin): array
    {
        $candidates = array_values(array_filter($this->candidates(), fn (array $candidate) => $category === '' || $candidate['category'] === $category));
        $ids = $tab === 'popular'
            ? $this->ranker->popular($candidates)
            : $this->ranker->rank($candidates, $this->context($user, $category !== ''));

        // Back from signing in: the quote the reader was on comes first.
        if ($pin && Quote::query()->whereKey($pin)->exists()) {
            $ids = array_merge([$pin], array_values(array_diff($ids, [$pin])));
        }

        $token = Str::random(24);
        Cache::put($this->snapshotKey($token), ['owner' => $this->owner($user), 'ids' => $ids], now()->addMinutes(config('feed.snapshot_minutes')));

        return ['token' => $token, 'ids' => $ids];
    }

    // Only what the site already stores: the reader's own likes and saves, and the categories and authors of those quotes.
    private function context(?User $user, bool $filtered): array
    {
        $context = [
            'now' => now()->getTimestamp(),
            'seed' => Str::random(16),
            'user_id' => $user?->id,
            'categories' => [],
            'authors' => [],
            'liked' => [],
            'seen' => array_flip(session(self::SEEN_SESSION, [])),
            'filtered' => $filtered,
        ];
        if (! $user) {
            return $context;
        }

        $interaction = fn (string $table) => DB::table($table)->join('quotes', 'quotes.id', '=', "$table.quote_id")
            ->where("$table.user_id", $user->id)->select('quotes.id', 'quotes.user_id', 'quotes.author', 'quotes.category');
        $rows = $interaction('quote_likes')->unionAll($interaction('quote_favorites'))->get();

        foreach ($rows as $row) {
            $context['liked'][$row->id] = true;
            $context['categories'][$row->category] = ($context['categories'][$row->category] ?? 0) + 1;
            $key = FeedRanker::authorKey((array) $row);
            $context['authors'][$key] = ($context['authors'][$key] ?? 0) + 1;
        }
        $total = max(1, $rows->count());
        $context['categories'] = array_map(fn (int $count) => $count / $total, $context['categories']);

        return $context;
    }

    /** The newest quotes plus the most appreciated, with their like and save counts; refreshed every few minutes and whenever a quote changes. */
    private function candidates(): array
    {
        return Cache::remember(self::CANDIDATES_CACHE, config('feed.candidates.cache_seconds'), function () {
            $base = fn () => Quote::query()->select(['id', 'user_id', 'author', 'category', 'created_at'])->withCount(['likes', 'favorites']);
            $rows = $base()->orderByDesc('created_at')->orderByDesc('id')->limit(config('feed.candidates.latest'))->get()
                // Counted again in ORDER BY: PostgreSQL cannot do arithmetic on the select aliases (SQLite can).
                ->concat($base()->orderByRaw('((select count(*) from quote_likes where quote_likes.quote_id = quotes.id) + (select count(*) from quote_favorites where quote_favorites.quote_id = quotes.id)) desc')
                    ->orderByDesc('id')->limit(config('feed.candidates.popular'))->get())
                ->unique('id');

            return $rows->map(fn (Quote $quote) => [
                'id' => $quote->id,
                'user_id' => $quote->user_id,
                'author' => (string) $quote->author,
                'category' => $quote->category,
                'created_at' => $quote->created_at?->getTimestamp() ?? 0,
                'likes' => (int) $quote->likes_count,
                'saves' => (int) $quote->favorites_count,
            ])->values()->all();
        });
    }

    private function rememberSeen(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $seen = array_values(array_unique(array_merge(session(self::SEEN_SESSION, []), $ids)));
        session([self::SEEN_SESSION => array_slice($seen, -config('feed.seen_memory'))]);
    }

    // A member's feed is personal, so only they can continue it. A guest feed holds nothing personal: its random token is enough,
    // which also keeps paging working for guests whose browser does not send the session cookie back.
    private function owner(?User $user): string
    {
        return $user ? 'u:'.$user->id : 'guest';
    }

    private function snapshotKey(string $token): string
    {
        return 'midad.discover.feed.'.hash('sha256', $token);
    }
}
