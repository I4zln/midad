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

test('wrong login shows an Arabic error under the password field and keeps the typed email', function () {
    \App\Models\User::factory()->create(['email' => 'reader@example.test', 'password' => 'correct-pass-1']);

    $this->from(route('login'))->post(route('login.store'), ['email' => 'reader@example.test', 'password' => 'wrong-pass'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['password' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة. تحقّق منهما وحاول مجددًا.']);

    $this->get(route('login'))->assertOk()
        ->assertSee('value="reader@example.test"', false)
        ->assertSee('id="password-error"', false)
        ->assertSee('<label class="field-label" for="email">البريد الإلكتروني', false);

    $this->from(route('login'))->post(route('login.store'), ['email' => 'not-an-email', 'password' => 'x'])
        ->assertSessionHasErrors(['email' => 'أدخل البريد الإلكتروني بصيغة صحيحة، مثل name@example.com.']);
    $this->assertGuest();
});

test('a guest action sends the reader to login with a reason and back to the same page afterwards', function () {
    $reader = \App\Models\User::factory()->create(['password' => 'correct-pass-1']);

    $this->get(route('login', ['next' => '/quotes?page=2', 'reason' => 'like']))->assertOk()
        ->assertSee('سجّل الدخول لتتمكن من الإعجاب بالاقتباس، وسنعيدك إلى حيث كنت.');
    $this->post(route('login.store'), ['email' => $reader->email, 'password' => 'correct-pass-1'])
        ->assertRedirect(url('/quotes?page=2'));
});

test('outside or protocol-relative next URLs are ignored', function () {
    $reader = \App\Models\User::factory()->create(['password' => 'correct-pass-1']);

    foreach (['https://evil.example/steal', '//evil.example/x', 'javascript:alert(1)'] as $next) {
        $this->get(route('login', ['next' => $next]))->assertOk();
        $this->post(route('login.store'), ['email' => $reader->email, 'password' => 'correct-pass-1'])->assertRedirect(route('home'));
        auth()->logout();
        session()->flush();
    }
});

test('opening a protected page as a guest explains why login is needed and returns there', function () {
    $reader = \App\Models\User::factory()->create(['password' => 'correct-pass-1']);

    $this->get(route('favorites'))->assertRedirect(route('login'));
    $this->get(route('login'))->assertSee('هذه الصفحة تحتاج إلى تسجيل الدخول، وسنعيدك إليها مباشرة بعده.');
    $this->post(route('login.store'), ['email' => $reader->email, 'password' => 'correct-pass-1'])->assertRedirect(route('favorites'));
});

test('forgot password gives the same neutral answer for known and unknown addresses and mails a working link', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $reader = \App\Models\User::factory()->create(['email' => 'known@example.test', 'password' => 'old-pass-123']);

    foreach (['known@example.test', 'unknown@example.test'] as $email) {
        $this->from(route('password.request'))->post(route('password.email'), ['email' => $email])
            ->assertSessionHas('status', 'إن كان البريد مسجّلًا لدينا فستصلك رسالة خلال دقائق.');
    }

    $token = null;
    \Illuminate\Support\Facades\Notification::assertSentTo($reader, \App\Notifications\EthraaResetPasswordNotification::class, function ($notification) use (&$token, $reader) {
        $token = $notification->token;
        $mail = $notification->toMail($reader);

        return $mail->subject === 'مِداد | طلب إعادة تعيين كلمة المرور';
    });

    $this->get(route('password.reset', ['token' => $token, 'email' => $reader->email]))->assertOk();
    $this->post(route('password.update'), ['token' => $token, 'email' => $reader->email, 'password' => 'new-pass-456', 'password_confirmation' => 'new-pass-456'])
        ->assertRedirect(route('login'))->assertSessionHas('status');
    $this->post(route('login.store'), ['email' => $reader->email, 'password' => 'new-pass-456'])->assertRedirect(route('home'));
});
