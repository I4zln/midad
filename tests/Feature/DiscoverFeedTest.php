<?php

use App\Models\Quote;
use App\Models\QuoteFavorite;
use App\Models\QuoteLike;
use App\Models\User;
use App\Services\FeedRanker;
use Illuminate\Support\Facades\DB;

// $count quotes, one per hour going back, spread over the four categories and a few authors.
function discoverQuotes(int $count, array $overrides = []): array
{
    $categories = ['تأمل', 'حياة', 'حب', 'أمل'];
    $authors = ['المتنبي', 'جبران', 'درويش', 'المعري', ''];
    $ids = [];
    for ($i = 0; $i < $count; $i++) {
        $quote = Quote::query()->forceCreate($overrides + [
            'text' => "كلمة رقم {$i}",
            'author' => $authors[$i % 5],
            'category' => $categories[$i % 4],
            'created_at' => now()->subHours($i + 1),
            'updated_at' => now()->subHours($i + 1),
        ]);
        $ids[] = $quote->id;
    }

    return $ids;
}

// Walks a feed from the page through every batch, carrying the session cookie like a browser does.
function walkDiscover($test, array $query = []): array
{
    $page = $test->get(route('discover', $query))->assertOk();
    $cookie = $page->getCookie(config('session.cookie'));
    $ids = array_map(fn ($quote) => $quote->id, $page->viewData('quotes')->all());
    $next = $page->viewData('next');
    $batches = 1;
    while ($next) {
        $request = $cookie ? $test->withCookie($cookie->getName(), $cookie->getValue()) : $test;
        $batch = $request->getJson(route('discover.feed', $query + ['cursor' => $next]))->assertOk();
        $ids = array_merge($ids, $batch->json('ids'));
        $next = $batch->json('next');
        $batches++;
        expect($batches)->toBeLessThan(50);
    }

    return [$ids, $batches];
}

test('guests can open «اكتشف» from the navbar, the homepage and the quotes page', function () {
    discoverQuotes(3);

    $this->get(route('home'))->assertOk()->assertSee('href="'.route('discover').'"', false)->assertSee('اكتشف واحدًا تلو الآخر');
    $this->get(route('quotes.index'))->assertOk()->assertSee('أو تصفّحها في «اكتشف»', false);
    $this->get(route('discover'))->assertOk()
        ->assertSee('class="nav-link is-active" href="'.route('discover').'"', false)
        ->assertSee('data-mode="focus"', false)
        ->assertSee('مختارات')->assertSee('الأحدث')->assertSee('الأشهر')
        ->assertSee('تأمّل')->assertSee('الخط الزمني')
        ->assertSee('role="feed"', false)
        ->assertSee('data-image-share', false);
});

test('only categories that have quotes appear as chips, with «الكل»', function () {
    Quote::query()->forceCreate(['text' => 'عن الحب', 'category' => 'حب']);
    Quote::query()->forceCreate(['text' => 'عن الأمل', 'category' => 'أمل']);

    $page = $this->get(route('discover'))->assertOk();
    expect($page->viewData('categories'))->toBe(['حب' => 'حب', 'أمل' => 'أمل']);
    $page->assertSee('>الكل</a>', false)->assertDontSee('>تأمّل</a>', false);
});

test('every tab loads through many batches without repeating or skipping a quote', function (string $tab) {
    $all = discoverQuotes(70);
    $reader = User::factory()->create();

    [$ids, $batches] = walkDiscover($this->actingAs($reader), ['tab' => $tab]);

    expect($batches)->toBe(6) // 12 per batch: 5 full batches and a last one of 10
        ->and($ids)->toHaveCount(70)
        ->and(array_unique($ids))->toHaveCount(70)
        ->and(array_diff($all, $ids))->toBe([]);
})->with(['for-you', 'latest', 'popular']);

test('guests page through the ranked feed without repeats too', function () {
    discoverQuotes(40);

    [$ids] = walkDiscover($this);

    expect($ids)->toHaveCount(40)->and(array_unique($ids))->toHaveCount(40);
});

test('«الأحدث» is newest first and «الأشهر» most liked and saved first', function () {
    [$newest, $middle, $oldest] = discoverQuotes(3);
    $fans = User::factory()->count(3)->create();
    foreach ($fans as $fan) {
        QuoteLike::query()->create(['quote_id' => $oldest, 'user_id' => $fan->id]);
    }
    QuoteFavorite::query()->create(['quote_id' => $middle, 'user_id' => $fans[0]->id]);

    expect(array_map(fn ($q) => $q->id, $this->get(route('discover', ['tab' => 'latest']))->viewData('quotes')->all()))->toBe([$newest, $middle, $oldest]);
    expect(array_map(fn ($q) => $q->id, $this->get(route('discover', ['tab' => 'popular']))->viewData('quotes')->all()))->toBe([$oldest, $middle, $newest]);
});

test('a category chip keeps the feed to that category; an unknown one shows everything', function () {
    discoverQuotes(20);

    [$ids] = walkDiscover($this->actingAs(User::factory()->create()), ['category' => 'حب']);
    expect($ids)->toHaveCount(5)
        ->and(Quote::query()->whereKey($ids)->pluck('category')->unique()->all())->toBe(['حب']);

    $this->get(route('discover', ['category' => 'غير-موجود']))->assertOk()->assertViewHas('category', '');
});

test('quotes the reader already liked or saved come after everything new to them', function () {
    $ids = discoverQuotes(30);
    $reader = User::factory()->create();
    $liked = array_slice($ids, 0, 4); // the four newest, which would otherwise rank near the top
    foreach ($liked as $id) {
        QuoteLike::query()->create(['quote_id' => $id, 'user_id' => $reader->id]);
    }
    QuoteFavorite::query()->create(['quote_id' => $ids[4], 'user_id' => $reader->id]);

    [$order] = walkDiscover($this->actingAs($reader));

    expect(array_slice($order, -5))->toEqualCanonicalizing([...$liked, $ids[4]]);
});

test('the ranked feed leans towards the categories and authors a reader liked', function () {
    $reader = User::factory()->create();
    // Two older quotes in «حب» that the reader liked, then 24 fresh quotes, a quarter of them in «حب».
    foreach (discoverQuotes(2, ['category' => 'حب', 'author' => 'نزار', 'created_at' => now()->subDays(20)]) as $id) {
        QuoteLike::query()->create(['quote_id' => $id, 'user_id' => $reader->id]);
    }
    discoverQuotes(24, ['author' => '']);

    $first = $this->actingAs($reader)->get(route('discover'))->viewData('quotes')->take(8)->pluck('category');

    expect($first->filter(fn ($category) => $category === 'حب')->count())->toBeGreaterThanOrEqual(4);
});

test('quotes seen earlier in the session move behind unseen ones on the next visit', function () {
    discoverQuotes(30);
    $reader = User::factory()->create();

    $firstVisit = $this->actingAs($reader)->get(route('discover'))->viewData('quotes')->modelKeys();
    $secondVisit = $this->actingAs($reader)->get(route('discover'))->viewData('quotes')->modelKeys();

    expect(array_intersect($firstVisit, $secondVisit))->toBe([]);
});

test('no author or category shows up more than twice in a row when there is an alternative', function () {
    // Half the library is one author in one category: without the rule it would fill the top of the feed.
    discoverQuotes(20, ['author' => 'المتنبي', 'category' => 'حياة', 'created_at' => now()]);
    discoverQuotes(20);

    [$ids] = walkDiscover($this->actingAs(User::factory()->create()));
    $quotes = Quote::query()->whereKey($ids)->get()->keyBy('id');
    $runs = ['author' => 0, 'category' => 0];
    for ($i = 2; $i < 30; $i++) {
        [$a, $b, $c] = [$quotes[$ids[$i - 2]], $quotes[$ids[$i - 1]], $quotes[$ids[$i]]];
        $runs['author'] += (int) ($a->author !== '' && $a->author === $b->author && $b->author === $c->author);
        $runs['category'] += (int) ($a->category === $b->category && $b->category === $c->category);
    }

    expect($runs)->toBe(['author' => 0, 'category' => 0]);
});

test('the ranker: freshness halves every half-life, appreciation grows on a log curve and is capped', function () {
    $ranker = new FeedRanker(['weights' => [], 'guest_weights' => ['freshness' => 1.0], 'freshness_half_life_hours' => 72, 'save_weight' => 1.5, 'appreciation_cap' => 50, 'author_affinity_full' => 3]);
    $context = ['now' => 1_000_000, 'seed' => 's', 'user_id' => null];
    $quote = fn (int $hoursOld, int $likes = 0) => ['id' => 1, 'user_id' => null, 'author' => '', 'category' => 'حب', 'created_at' => 1_000_000 - $hoursOld * 3600, 'likes' => $likes, 'saves' => 0];

    expect($ranker->score($quote(0), $context))->toBe(1.0)
        ->and($ranker->score($quote(72), $context))->toEqualWithDelta(0.5, 1e-9)
        ->and($ranker->score($quote(144), $context))->toEqualWithDelta(0.25, 1e-9);

    $appreciation = new FeedRanker(['weights' => [], 'guest_weights' => ['appreciation' => 1.0], 'save_weight' => 1.5, 'appreciation_cap' => 50, 'freshness_half_life_hours' => 72]);
    $one = $appreciation->score($quote(0, 1), $context);
    $ten = $appreciation->score($quote(0, 10), $context);
    $viral = $appreciation->score($quote(0, 5000), $context);
    expect($ten)->toBeLessThan($one * 4) // ten likes are worth far less than ten times one like
        ->and($viral)->toBe(1.0)
        ->and($appreciation->score($quote(0, 50), $context))->toEqualWithDelta(1.0, 1e-9);
});

test('the ranker is deterministic for a seed and varies between seeds', function () {
    $ranker = FeedRanker::fromConfig();
    $candidates = array_map(fn ($i) => ['id' => $i, 'user_id' => $i % 3, 'author' => 'ك'.($i % 6), 'category' => ['حب', 'أمل', 'حياة', 'تأمل'][$i % 4], 'created_at' => 1_000_000 - $i * 3600, 'likes' => $i % 5, 'saves' => 0], range(1, 40));
    $context = fn ($seed) => ['now' => 1_000_000, 'seed' => $seed, 'user_id' => null, 'categories' => [], 'authors' => [], 'liked' => [], 'seen' => [], 'filtered' => false];

    expect($ranker->rank($candidates, $context('a')))->toBe($ranker->rank($candidates, $context('a')))
        ->and($ranker->rank($candidates, $context('a')))->not->toBe($ranker->rank($candidates, $context('b')))
        ->and($ranker->rank($candidates, $context('a')))->toEqualCanonicalizing(range(1, 40));
});

test('a new quote enters the ranked feed straight away', function () {
    discoverQuotes(5);
    $this->get(route('discover'))->assertOk();

    $new = Quote::query()->forceCreate(['text' => 'كلمة وصلت الآن', 'category' => 'أمل']);

    expect($this->get(route('discover'))->viewData('quotes')->modelKeys())->toContain($new->id);
});

test('a forged or broken cursor is refused (422, Arabic message) instead of restarting the feed', function (string $tab) {
    discoverQuotes(3);

    $this->getJson(route('discover.feed', ['tab' => $tab, 'cursor' => 'not-a-cursor']))
        ->assertStatus(422)->assertJsonPath('message', 'تعذّر متابعة هذه القائمة. حدّث الصفحة لتبدأ من جديد.');
})->with(['for-you', 'latest', 'popular']);

test('guests are sent to sign in with a reason and come back to the same quote first', function () {
    $ids = discoverQuotes(30);
    $target = $ids[20];
    $back = route('discover', ['quote' => $target], false);

    $this->get(route('discover', ['quote' => $target]))->assertOk()
        ->assertViewHas('quotes', fn ($quotes) => $quotes->first()->id === $target)
        ->assertSee(e(route('login', ['next' => $back, 'reason' => 'like'])), false)
        ->assertSee('data-guest-action="like"', false)
        ->assertSee('data-guest-action="save"', false);

    $this->get(route('login', ['next' => $back, 'reason' => 'like']))->assertOk()->assertSee('سجّل الدخول لتتمكن من الإعجاب بالاقتباس');
    $user = User::factory()->create(['email' => 'reader@example.test', 'password' => 'password-123']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password-123'])->assertRedirect(url($back));
});

test('likes and saves made in the feed are stored and show after a refresh', function () {
    [$quoteId] = discoverQuotes(3);
    $reader = User::factory()->create();

    $this->actingAs($reader)->postJson(route('quotes.like', $quoteId))->assertOk()->assertJson(['liked' => true]);
    $this->actingAs($reader)->postJson(route('quotes.favorite', $quoteId))->assertOk()->assertJson(['saved' => true]);

    $html = $this->actingAs($reader)->get(route('discover', ['tab' => 'latest']))->assertOk()->getContent();
    $card = substr($html, strpos($html, 'data-quote-id="'.$quoteId.'"'), 6000);
    expect($card)->toContain('like-button is-liked')->toContain('aria-pressed="true"')->toContain('is-favorited');
});

test('members keep their chosen view across devices; guests cannot write it', function () {
    discoverQuotes(2);
    $reader = User::factory()->create();

    $this->actingAs($reader)->postJson(route('discover.mode'), ['mode' => 'timeline'])->assertNoContent();
    expect($reader->fresh()->discover_mode)->toBe('timeline');
    $this->actingAs($reader)->get(route('discover'))->assertSee('data-mode="timeline"', false)->assertSee('data-mode-account', false);
    $this->actingAs($reader)->postJson(route('discover.mode'), ['mode' => 'sideways'])->assertStatus(422);

    auth()->logout();
    $this->postJson(route('discover.mode'), ['mode' => 'focus'])->assertUnauthorized();
});

test('a feed batch runs the same few queries however many quotes it holds (no N+1)', function () {
    $reader = User::factory()->create();
    $count = function (int $quotes) use ($reader) {
        Quote::query()->delete();
        discoverQuotes($quotes);
        foreach (Quote::query()->pluck('id') as $id) {
            QuoteLike::query()->create(['quote_id' => $id, 'user_id' => User::factory()->create()->id]);
        }
        $cursor = $this->actingAs($reader)->getJson(route('discover.feed', ['tab' => 'latest']))->json('next');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($reader)->getJson(route('discover.feed', ['tab' => 'for-you']))->assertOk();
        $ranked = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->actingAs($reader)->getJson(route('discover.feed', ['tab' => 'latest']))->assertOk();
        $latest = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$ranked, $latest];
    };

    [$smallRanked, $smallLatest] = $count(3);
    [$bigRanked, $bigLatest] = $count(40);

    expect($bigRanked)->toBe($smallRanked)->and($bigLatest)->toBe($smallLatest)
        ->and($bigRanked)->toBeLessThanOrEqual(6)->and($bigLatest)->toBeLessThanOrEqual(4);
});

test('the existing quotes page still lists 12 per page with its search and filters', function () {
    discoverQuotes(30);

    $this->get(route('quotes.index'))->assertOk()
        ->assertViewHas('quotes', fn ($page) => $page->count() === 12 && $page->total() === 30)
        ->assertSee('name="q"', false)->assertSee('ترقيم صفحات الاقتباسات');
    $this->get(route('quotes.index', ['category' => 'حياة', 'q' => 'كلمة']))->assertOk()
        ->assertViewHas('quotes', fn ($page) => $page->total() === 8)
        ->assertSee('مسح البحث والفلاتر ×');
});
