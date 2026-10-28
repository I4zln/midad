<?php

use App\Models\Quote;
use App\Models\User;

test('saving a quote answers with JSON for in-place updates and still redirects without JavaScript', function () {
    $user = User::factory()->create();
    $quote = Quote::query()->forceCreate(['text' => 'كلمة تُحفظ', 'category' => 'حياة', 'user_id' => $user->id]);

    $this->actingAs($user)->postJson(route('quotes.favorite', $quote))
        ->assertOk()->assertJson(['saved' => true, 'message' => 'حُفظ الاقتباس في محفوظاتك.']);
    $this->actingAs($user)->postJson(route('quotes.favorite', $quote))
        ->assertOk()->assertJson(['saved' => false]);

    $this->actingAs($user)->from(route('quotes.index'))->post(route('quotes.favorite', $quote))
        ->assertRedirect(route('quotes.index'))->assertSessionHas('status');
});

test('cached author counts refresh when a quote is added, edited or deleted', function () {
    $quote = Quote::query()->forceCreate(['text' => 'بيت أول', 'author' => 'المتنبي', 'category' => 'حياة']);
    expect(Quote::authorCounts()->firstWhere('author', 'المتنبي')->quotes_count)->toBe(1);

    Quote::query()->forceCreate(['text' => 'بيت ثانٍ', 'author' => 'المتنبي', 'category' => 'حياة']);
    expect(Quote::authorCounts()->firstWhere('author', 'المتنبي')->quotes_count)->toBe(2);

    $quote->update(['author' => 'المعري']);
    expect(Quote::authorCounts()->firstWhere('author', 'المعري')->quotes_count)->toBe(1);

    $quote->delete();
    expect(Quote::authorCounts()->firstWhere('author', 'المعري'))->toBeNull();
});

test('the library total stays correct with and without filters', function () {
    foreach (['حياة', 'حياة', 'أمل'] as $index => $category) {
        Quote::query()->forceCreate(['text' => "اقتباس {$index}", 'category' => $category]);
    }

    $this->get(route('quotes.index'))->assertViewHas('totalQuotes', 3)->assertViewHas('quotes', fn ($page) => $page->total() === 3);
    $this->get(route('quotes.index', ['category' => 'أمل']))->assertViewHas('totalQuotes', 3)->assertViewHas('quotes', fn ($page) => $page->total() === 1);
});
