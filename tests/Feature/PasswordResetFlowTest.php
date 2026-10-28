<?php

use App\Http\Controllers\PasswordResetController;
use App\Models\User;
use App\Notifications\EthraaResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

test('the forgot-password page shows no developer notes', function () {
    $this->get(route('password.request'))->assertOk()
        ->assertDontSee('وضع التطوير')
        ->assertDontSee('mail.log');
});

test('an address typed with capitals still gets the reset email, and the new password works the same way', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reader@example.test', 'password' => 'old-password-1']);

    $this->post(route('password.email'), ['email' => '  Reader@Example.TEST '])->assertSessionHas('status', PasswordResetController::LINK_SENT);

    $token = null;
    Notification::assertSentTo($user, EthraaResetPasswordNotification::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->post(route('password.update'), ['token' => $token, 'email' => 'READER@example.test', 'password' => 'new-password-2', 'password_confirmation' => 'new-password-2'])
        ->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
    auth()->logout();

    $this->post(route('login.store'), ['email' => 'Reader@Example.test', 'password' => 'new-password-2'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('registering with a capitalised copy of an existing address is refused', function () {
    User::factory()->create(['email' => 'reader@example.test']);

    $this->post(route('register.store'), ['name' => 'قارئ', 'email' => 'Reader@Example.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
        ->assertSessionHasErrors('email');
    expect(User::query()->count())->toBe(1);
});

test('in production without a mail service, the form says the service is unavailable instead of promising an email', function () {
    Notification::fake();
    app()->detectEnvironment(fn () => 'production');
    // Laravel only skips CSRF checks in the testing environment.
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    config(['mail.default' => 'log', 'app.force_https' => false]);
    $user = User::factory()->create(['email' => 'reader@example.test']);

    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'reader@example.test'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email' => PasswordResetController::UNAVAILABLE])
        ->assertSessionMissing('status');

    Notification::assertNothingSent();
    expect(DB::table('password_reset_tokens')->count())->toBe(0);
});
