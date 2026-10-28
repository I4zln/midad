<?php

use App\Models\Quote;
use App\Models\User;

test('the home hero shows the newest quote with who shared it', function () {
    $older = User::factory()->create(['name' => 'قارئة أولى']);
    $newer = User::factory()->create(['name' => 'قارئ ثانٍ']);
    Quote::query()->forceCreate(['text' => 'اقتباس أقدم', 'category' => 'حياة', 'user_id' => $older->id, 'created_at' => now()->subDay()]);
    $latest = Quote::query()->forceCreate(['text' => 'أحدث كلمة في المكتبة', 'author' => 'كاتب الكلمة', 'category' => 'أمل', 'user_id' => $newer->id]);

    $this->get(route('home'))->assertOk()
        ->assertSee('aria-label="أحدث اقتباس في المكتبة"', false)
        ->assertSeeInOrder(['أحدث اقتباس', 'أمل', 'أحدث كلمة في المكتبة', '— كاتب الكلمة', 'href="'.route('profile.show', $newer).'"', 'قارئ ثانٍ', 'شاركه'], false)
        ->assertSee('href="'.route('quotes.show', $latest).'"', false);
});

test('a quote whose account is gone is credited to «قارئ سابق», and an empty library keeps the welcome text', function () {
    $this->get(route('home'))->assertOk()->assertSee('الكلمات التي نحبها تستحق مكانًا نعود إليه.');

    Quote::query()->forceCreate(['text' => 'كلمة من حساب محذوف', 'category' => 'حياة', 'user_id' => null]);
    $this->get(route('home'))->assertOk()
        ->assertSee('كلمة من حساب محذوف')
        ->assertSeeInOrder(['كلمة من حساب محذوف', 'قارئ سابق', 'شاركه']);
});
