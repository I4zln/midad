<?php

test('the old typed welcome message is gone and the optional audio menu remains without autoplay', function () {
    foreach ([route('home'), route('quotes.index'), route('login')] as $url) {
        $page = $this->get($url)->assertOk()
            ->assertDontSee('شكرًا لكل من جرّب مِداد')
            ->assertDontSee('ethraa-welcome', false)
            ->assertSee('class="ambient-menu"', false)
            ->assertSee('id="ethraa-audio-choice"', false)
            ->assertSee('صوت اختياري، لا يعمل إلا إذا اخترته.');

        expect($page->getContent())->not->toMatch('/<audio[^>]*autoplay/');
    }
});

test('the ink welcome greets guests generically and signed-in readers by their escaped name', function () {
    $this->get(route('home'))->assertOk()
        ->assertSee('data-ink-welcome', false)
        ->assertSee('أهلًا بك في مِـداد')
        ->assertSee('هنا يهدأُ الحبرُ على الورق، وتبقى الكلمةُ أثرًا لا يجفّ.')
        ->assertSee('fonts/amiri/amiri-bold-arabic.woff2', false);

    $reader = \App\Models\User::factory()->create(['name' => '<b>ريم</b>']);
    $this->actingAs($reader)->get(route('home'))->assertOk()
        ->assertSee('أهلًا بك يا')
        ->assertSee('&lt;b&gt;ريم&lt;/b&gt;', false)
        ->assertDontSee('<b>ريم</b>', false);

    expect(substr_count($this->get(route('home'))->getContent(), 'لأن للكلمات أثرًا يبقى'))->toBe(1);
});
