<?php

use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function countQueriesFor(callable $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('the home and quotes pages run a constant number of queries (no N+1)', function () {
    $reader = User::factory()->create();
    $seed = function (int $amount) use ($reader) {
        foreach (range(1, $amount) as $number) {
            $author = User::factory()->create();
            $quote = Quote::query()->create(['user_id' => $author->id, 'text' => "نص للقياس {$number}.", 'category' => 'حياة']);
            $quote->likes()->create(['user_id' => $reader->id]);
        }
    };

    $this->actingAs($reader);
    $seed(3);
    $homeSmall = countQueriesFor(fn () => $this->get(route('home'))->assertOk());
    $listSmall = countQueriesFor(fn () => $this->get(route('quotes.index'))->assertOk());

    $seed(20);
    $homeLarge = countQueriesFor(fn () => $this->get(route('home'))->assertOk());
    $listLarge = countQueriesFor(fn () => $this->get(route('quotes.index'))->assertOk());

    expect($homeLarge)->toBe($homeSmall)
        ->and($listLarge)->toBe($listSmall);
});

test('the quotes table has indexes for newest-first ordering and category filtering', function () {
    $indexes = collect(DB::select("select name from sqlite_master where type = 'index' and tbl_name = 'quotes'"))->pluck('name');

    expect($indexes)->toContain('quotes_latest_index')
        ->toContain('quotes_category_created_at_index');
});
