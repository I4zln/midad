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
