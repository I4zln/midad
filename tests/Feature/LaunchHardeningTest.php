<?php

use App\Models\Quote;
use App\Models\User;
use App\Models\UserNotification;

// Launch review: per-member limits on members' own actions, no repeated follow notices, no version headers,
// and the home ranking reused between visits but refreshed by every like.

it('limits how fast one member can comment', function () {
    $member = User::factory()->create();
    $quote = Quote::query()->create(['user_id' => User::factory()->create()->id, 'text' => 'اقتباس للتعليق', 'category' => 'أمل']);

    for ($i = 1; $i <= 10; $i++) {
        $this->actingAs($member)->post(route('quotes.comments.store', $quote), ['body' => "تعليق {$i}"])->assertRedirect();
    }
    $this->actingAs($member)->post(route('quotes.comments.store', $quote), ['body' => 'تعليق زائد'])->assertStatus(429);
    expect($quote->comments()->count())->toBe(10);
});

it('limits likes per member without touching other members', function () {
    $quote = Quote::query()->create(['user_id' => User::factory()->create()->id, 'text' => 'اقتباس للإعجاب', 'category' => 'أمل']);
    $busy = User::factory()->create();

    for ($i = 1; $i <= 60; $i++) {
        $this->actingAs($busy)->postJson(route('quotes.like', $quote))->assertOk();
    }
    $this->actingAs($busy)->postJson(route('quotes.like', $quote))->assertStatus(429);
    $this->actingAs(User::factory()->create())->postJson(route('quotes.like', $quote))->assertOk();
});

it('does not repeat the follow notification when someone follows, unfollows and follows again', function () {
    $follower = User::factory()->create();
    $followed = User::factory()->create();

    foreach (range(1, 3) as $round) {
        $this->actingAs($follower)->post(route('users.follow', $followed)); // follow
        $this->actingAs($follower)->post(route('users.follow', $followed)); // unfollow
    }
    $this->actingAs($follower)->post(route('users.follow', $followed));

    expect(UserNotification::query()->where('user_id', $followed->id)->where('type', 'follow')->count())->toBe(1);

    // Once read, a later follow is news again.
    UserNotification::query()->update(['read_at' => now()]);
    $this->actingAs($follower)->post(route('users.follow', $followed));
    $this->actingAs($follower)->post(route('users.follow', $followed));
    expect(UserNotification::query()->where('user_id', $followed->id)->where('type', 'follow')->count())->toBe(2);
});

it('does not send X-Powered-By', function () {
    expect($this->get(route('home'))->headers->has('X-Powered-By'))->toBeFalse();
});

it('keeps the home ranking between visits and refreshes it when a like changes the order', function () {
    config(['cache.default' => 'array']);
    $owner = User::factory()->create();
    $first = Quote::query()->create(['user_id' => $owner->id, 'text' => 'الأول', 'category' => 'أمل']);
    $second = Quote::query()->create(['user_id' => $owner->id, 'text' => 'الثاني', 'category' => 'أمل']);
    $first->likes()->create(['user_id' => User::factory()->create()->id]);

    expect(Quote::leaderboard()->first()->is($first))->toBeTrue()
        ->and(cache()->has(Quote::LEADERBOARD_CACHE))->toBeTrue();

    $second->likes()->create(['user_id' => User::factory()->create()->id]);
    $second->likes()->create(['user_id' => User::factory()->create()->id]);

    expect(Quote::leaderboard()->first()->is($second))->toBeTrue()
        ->and(Quote::leaderboard()->first()->likes_count)->toBe(2);
});

it('links the publisher name on a quote card to their profile, in «اكتشف» and the library', function () {
    $publisher = User::factory()->create(['name' => 'ناشرة الاقتباس']);
    Quote::query()->create(['user_id' => $publisher->id, 'text' => 'اقتباس له ناشر', 'category' => 'أمل']);
    Quote::query()->create(['user_id' => null, 'text' => 'اقتباس بلا ناشر', 'category' => 'أمل']);
    $link = '<a class="quote-card-publisher-link" href="'.route('profile.show', $publisher).'"><strong>ناشرة الاقتباس</strong></a>';

    $this->get(route('discover', ['tab' => 'latest']))->assertOk()->assertSee($link, false)->assertSee('<strong>قارئ سابق</strong>', false);
    $this->get(route('quotes.index'))->assertOk()->assertSee($link, false);
});
