<?php

use App\Models\User;
use App\Notifications\EthraaResetPasswordNotification;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

test('password reset requests use one response for registered and unregistered addresses', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reader@example.test']);

    $known = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);
    $unknown = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'missing@example.test']);

    $known->assertRedirect(route('password.request'));
    $unknown->assertRedirect(route('password.request'));
    expect($known->getSession()->get('status'))->toBe($unknown->getSession()->get('status'));
    Notification::assertSentTo($user, EthraaResetPasswordNotification::class);
    expect(DB::table('password_reset_tokens')->where('email', 'missing@example.test')->exists())->toBeFalse();
});

test('malformed email receives a local validation error without sending a notification', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'not-an-email'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email']);

    Notification::assertNothingSent();
});

test('reset email uses Midad branding and the broker expiry duration', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reader@example.test']);

    $this->post(route('password.email'), ['email' => $user->email]);

    $notification = Notification::sent($user, EthraaResetPasswordNotification::class)->first();
    expect($notification)->not->toBeNull();

    $mail = $notification->toMail($user);
    expect($mail->subject)->toBe('مِداد | طلب إعادة تعيين كلمة المرور')
        ->and($mail->view['html'])->toBe('mail.password-reset')
        ->and($mail->view['text'])->toBe('mail.password-reset-text')
        ->and($mail->viewData['expiresInMinutes'])->toBe(config('auth.passwords.users.expire'));

    $html = view($mail->view['html'], $mail->viewData)->render();
    expect($html)->toContain('إعادة تعيين كلمة المرور')
        ->and($html)->toContain('الرابط التالي')
        ->and($html)->toContain($notification->token)
        ->and($html)->toContain('فريق مِداد | لأن للكلمات أثرًا يبقى');
});

test('a valid reset link can be opened and a new password can be used to sign in', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);
    $oldRememberToken = $user->remember_token;
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $user->refresh();
    expect(Hash::check('new-password-456', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldRememberToken)
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();

    $this->post(route('login.store'), [
        'identifier' => $user->email,
        'password' => 'new-password-456',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('password mismatch is rejected and the reset token remains usable', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);
    $token = Password::broker()->createToken($user);

    $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'different-password',
        ])->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertSessionHasErrors(['password']);

    expect(Password::broker()->tokenExists($user->fresh(), $token))->toBeTrue();
    $this->assertGuest();
});

test('expired and previously used reset links cannot be opened or reused', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email']);

    Password::broker()->deleteToken($user);
    $freshToken = Password::broker()->createToken($user);
    $this->post(route('password.update'), [
        'token' => $freshToken,
        'email' => $user->email,
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('login'));

    $this->get(route('password.reset', ['token' => $freshToken, 'email' => $user->email]))
        ->assertRedirect(route('password.request'));
    $this->post(route('password.update'), [
        'token' => $freshToken,
        'email' => $user->email,
        'password' => 'another-password-789',
        'password_confirmation' => 'another-password-789',
    ])->assertSessionHasErrors(['email']);
});

test('failed SMTP delivery is not reported as sent and its unusable token is removed', function () {
    $user = User::factory()->create(['email' => 'reader@example.test']);
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 1,
        'mail.mailers.smtp.username' => null,
        'mail.mailers.smtp.password' => null,
        'mail.from.address' => 'sender@example.test',
        'mail.from.name' => 'مِداد | MIDAD',
    ]);
    Mail::purge('smtp');
    Log::spy();

    $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'إذا كان البريد مرتبطًا بحساب، فستصلك رسالة إذا نجح الإرسال. تحقق من الوارد، وأعد المحاولة لاحقًا إن لم تصل.')
        ->assertSessionMissing('errors');

    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();
    Log::shouldHaveReceived('warning')->once()->with('Midad password reset notification delivery failed.');
});

test('password reset POST requests are rate limited', function () {
    RateLimiter::clear('password.update|127.0.0.1');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('password.update'), [])->assertSessionHasErrors(['token', 'email', 'password']);
    }

    $this->post(route('password.update'), [])->assertTooManyRequests();
});

test('password reset email requests are rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('password.email'), ['email' => 'not-an-email'])->assertSessionHasErrors(['email']);
    }

    $this->post(route('password.email'), ['email' => 'not-an-email'])->assertTooManyRequests();
});
