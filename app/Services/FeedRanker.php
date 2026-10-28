<?php

namespace App\Services;

// Orders quotes for the «اكتشف» feed. Pure and deterministic: the same candidates, reader and seed give the same order.
// The weights live in config/feed.php; docs/FEED_ALGORITHM.md describes the rules in plain language.
//
// A candidate is ['id', 'user_id', 'author', 'category', 'created_at' (unix time), 'likes', 'saves'].
// The context is ['now', 'seed', 'user_id' (null for guests), 'categories' => [category => share 0–1],
// 'authors' => [author key => interactions], 'liked' => [id => true], 'seen' => [id => true], 'filtered' => bool].
class FeedRanker
{
    public function __construct(private array $config) {}

    public static function fromConfig(): self
    {
        return new self(config('feed'));
    }

    /** @return list<int> quote ids, best first */
    public function rank(array $candidates, array $context): array
    {
        $scored = [];
        foreach ($candidates as $candidate) {
            $scored[] = $candidate + [
                'score' => $this->score($candidate, $context),
                // Unseen first, then quotes already shown this session, and last what the reader already liked or saved.
                'tier' => isset($context['liked'][$candidate['id']]) ? 2 : (isset($context['seen'][$candidate['id']]) ? 1 : 0),
                'newcomer' => $this->isNewcomer($candidate, $context['now']),
            ];
        }
        usort($scored, fn (array $a, array $b) => [$a['tier'], $b['score'], $b['id']] <=> [$b['tier'], $a['score'], $a['id']]);

        return $this->arrange($scored, (bool) ($context['filtered'] ?? false));
    }

    /** «الأشهر»: most likes and saves first, newer first on a tie. No personal signals. */
    public function popular(array $candidates): array
    {
        usort($candidates, fn (array $a, array $b) => [$this->reactions($b), $b['created_at'], $b['id']] <=> [$this->reactions($a), $a['created_at'], $a['id']]);

        return array_column($candidates, 'id');
    }

    public function score(array $candidate, array $context): float
    {
        $personal = $context['user_id'] !== null;
        $weights = $personal ? $this->config['weights'] : $this->config['guest_weights'];
        $signals = [
            'freshness' => $this->freshness($candidate, $context['now']),
            'appreciation' => $this->appreciation($candidate),
            'discovery' => $this->discovery($context['seed'], $candidate['id']),
        ];
        if ($personal) {
            $signals['category_affinity'] = $context['categories'][$candidate['category']] ?? 0.0;
            $signals['author_affinity'] = min(1.0, ($context['authors'][self::authorKey($candidate)] ?? 0) / max(1, $this->config['author_affinity_full']));
            $signals['own_quote'] = $candidate['user_id'] !== null && $candidate['user_id'] === $context['user_id'] ? 1.0 : 0.0;
        }

        $score = 0.0;
        foreach ($signals as $name => $value) {
            $score += ($weights[$name] ?? 0) * $value;
        }

        return $score;
    }

    // The same author in a row means the same poet or writer; quotes without one count by who published them.
    public static function authorKey(array $candidate): string
    {
        $author = trim((string) $candidate['author']);

        return $author !== '' ? 'a:'.$author : 'u:'.$candidate['user_id'];
    }

    private function freshness(array $candidate, int $now): float
    {
        $hours = max(0, $now - $candidate['created_at']) / 3600;

        return 0.5 ** ($hours / max(1, $this->config['freshness_half_life_hours']));
    }

    // log(1 + n) / log(1 + cap): the first likes count most, and a viral quote tops out at 1 like any other well-liked one.
    private function appreciation(array $candidate): float
    {
        return min(1.0, log(1 + $this->reactions($candidate)) / log(1 + max(1, $this->config['appreciation_cap'])));
    }

    private function reactions(array $candidate): float
    {
        return $candidate['likes'] + $this->config['save_weight'] * $candidate['saves'];
    }

    // Seeded per feed, so the order holds while scrolling but differs between visits.
    private function discovery(string $seed, int $id): float
    {
        return hexdec(substr(md5($seed.':'.$id), 0, 8)) / 0xFFFFFFFF;
    }

    private function isNewcomer(array $candidate, int $now): bool
    {
        return $now - $candidate['created_at'] <= $this->config['newcomer_days'] * 86400
            && $this->reactions($candidate) <= $this->config['newcomer_max_reactions'];
    }

    // Walk the scored list, picking the best quote that keeps the feed varied and saving every Nth place for a newcomer.
    // A quote is never pulled ahead of a better tier (unseen → seen → liked), only reordered within it.
    private function arrange(array $sorted, bool $sameCategory): array
    {
        $every = (int) $this->config['newcomer_every'];
        $order = [];
        $recent = [];

        while ($sorted !== []) {
            $tier = $sorted[array_key_first($sorted)]['tier'];
            $pick = null;

            if ($every > 0 && (count($order) + 1) % $every === 0) {
                foreach ($sorted as $key => $candidate) {
                    if ($candidate['tier'] !== $tier) {
                        break;
                    }
                    if ($candidate['newcomer'] && $this->keepsVariety($candidate, $recent, $sameCategory)) {
                        $pick = $key;
                        break;
                    }
                }
            }

            if ($pick === null) {
                foreach ($sorted as $key => $candidate) {
                    if ($candidate['tier'] !== $tier) {
                        break;
                    }
                    if ($this->keepsVariety($candidate, $recent, $sameCategory)) {
                        $pick = $key;
                        break;
                    }
                }
            }

            $pick ??= array_key_first($sorted);
            $chosen = $sorted[$pick];
            unset($sorted[$pick]);
            $order[] = $chosen['id'];
            $recent[] = $chosen;
            $recent = array_slice($recent, -$this->config['max_in_a_row']);
        }

        return $order;
    }

    private function keepsVariety(array $candidate, array $recent, bool $sameCategory): bool
    {
        $limit = $this->config['max_in_a_row'];
        if (count($recent) < $limit) {
            return true;
        }

        $key = self::authorKey($candidate);
        $sameAuthor = array_filter($recent, fn (array $previous) => self::authorKey($previous) === $key);
        $sameCategoryRun = array_filter($recent, fn (array $previous) => $previous['category'] === $candidate['category']);

        return count($sameAuthor) < $limit && ($sameCategory || count($sameCategoryRun) < $limit);
    }
}
