<?php

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => config(['ethraa.special_profile_email' => 'special-reader@example.test']));

test('the special account signs in like everyone else and lands on its profile, not a blocking verify page', function () {
    $user = User::factory()->unverified()->create(['email' => 'special-reader@example.test', 'password' => 'secret-pass-1']);

    $this->post(route('login.store'), ['email' => 'special-reader@example.test', 'password' => 'secret-pass-1'])
        ->assertRedirect(route('profile.show', $user))
        ->assertSessionHas('status', fn ($status) => str_contains($status, 'لديك رسالة خاصة'));

    $this->get(route('home'))->assertOk();
    $this->get(route('quotes.create'))->assertOk();
});

test('while unverified, the owner sees how to unlock the card; nobody else does; the private message stays hidden', function () {
    $user = User::factory()->unverified()->create(['email' => 'special-reader@example.test']);

    config(['mail.default' => 'log']);
    $this->actingAs($user)->get(route('profile.show', $user))->assertOk()
        ->assertSee('بطاقة تعريف بانتظارك')
        ->assertSee('خدمة البريد غير متصلة الآن')
        ->assertDontSee('أرسل رابط التأكيد')
        ->assertDontSee('إلى زياد إمام');

    config(['mail.default' => 'smtp']);
    $this->actingAs($user)->get(route('profile.show', $user))->assertSee('أرسل رابط التأكيد');

    $this->actingAs(User::factory()->create())->get(route('profile.show', $user))->assertOk()->assertDontSee('بطاقة تعريف بانتظارك');
});

test('after verification the private message appears and the waiting card is gone', function () {
    $user = User::factory()->create(['email' => 'special-reader@example.test']);

    $this->actingAs($user)->get(route('profile.show', $user))->assertOk()
        ->assertSee('aria-labelledby="member-idcard-name"', false)
        ->assertDontSee('بطاقة تعريف بانتظارك');
});

test('registering with the special email sends one verification link only when mail is connected, and never blocks', function () {
    Notification::fake();
    config(['mail.default' => 'log']);
    $this->post(route('register.store'), ['name' => 'قارئ خاص', 'email' => 'special-reader@example.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
        ->assertRedirect();
    Notification::assertNothingSent();
    $this->assertAuthenticated();

    auth()->logout();
    User::query()->delete();
    config(['mail.default' => 'smtp']);
    $this->post(route('register.store'), ['name' => 'قارئ خاص', 'email' => 'special-reader@example.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1']);
    Notification::assertSentTimes(VerifyEmail::class, 1);
});

test('resending the link reports in Arabic, not internal codes', function () {
    config(['mail.default' => 'log']);
    $user = User::factory()->unverified()->create(['email' => 'special-reader@example.test']);

    $this->actingAs($user)->from(route('profile.show', $user))->post(route('verification.send'))
        ->assertRedirect(route('profile.show', $user))
        ->assertSessionHas('status', AuthController::LINK_FAILED);
});

test('a verified special account goes to its profile until the message has been shown, then signs in normally', function () {
    $user = User::factory()->create(['email' => 'special-reader@example.test', 'password' => 'secret-pass-1']);
    $credentials = ['email' => 'special-reader@example.test', 'password' => 'secret-pass-1'];

    $this->post(route('login.store'), $credentials)->assertRedirect(route('profile.show', $user));
    $this->get(route('profile.show', $user))->assertSee('aria-labelledby="member-idcard-name"', false);

    auth()->logout();
    $this->post(route('login.store'), $credentials)->assertRedirect(route('home'));
});

test('the private message stays on the owner profile after a refresh and is never shown to anyone else', function () {
    $user = User::factory()->create(['email' => 'special-reader@example.test']);

    $this->actingAs($user)->get(route('profile.show', $user))->assertSee('aria-labelledby="member-idcard-name"', false);
    $this->actingAs($user)->get(route('profile.show', $user))->assertSee('aria-labelledby="member-idcard-name"', false);
    $this->actingAs(User::factory()->create())->get(route('profile.show', $user))->assertDontSee('member-idcard-name');
    $this->get(route('profile.show', $user))->assertDontSee('member-idcard-name');
});
