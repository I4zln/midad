<?php

use App\Http\Controllers\PasswordResetController;
use App\Models\PasswordHelpRequest;
use App\Models\User;
use App\Models\UserNotification;

// «نسيت كلمة المرور» while email cannot be sent: the request reaches the admin panel, the admins are notified once,
// and the form answers the same for every address.

function noMailHost($test): void
{
    app()->detectEnvironment(fn () => 'production');
    $test->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    config(['mail.default' => 'log', 'app.force_https' => false]);
}

it('queues one request per member, notifies the admins once, and answers unknown addresses the same way', function () {
    noMailHost($this);
    $admin = tap(User::factory()->create(), fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    $member = User::factory()->create(['name' => 'قارئة نسيت', 'email' => 'forgot@example.test']);

    $this->post(route('password.email'), ['email' => 'Forgot@Example.test'])->assertSessionHas('status', PasswordResetController::HELP_QUEUED);
    $this->post(route('password.email'), ['email' => 'forgot@example.test'])->assertSessionHas('status', PasswordResetController::HELP_QUEUED);
    $this->post(route('password.email'), ['email' => 'nobody@example.test'])->assertSessionHas('status', PasswordResetController::HELP_QUEUED);

    expect(PasswordHelpRequest::query()->count())->toBe(1)
        ->and(PasswordHelpRequest::query()->first()->user_id)->toBe($member->id)
        ->and(UserNotification::query()->where('user_id', $admin->id)->where('type', 'recovery')->count())->toBe(1);
});

it('lists open requests in the admin panel and closes one when its link is created', function () {
    $admin = tap(User::factory()->create(), fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    $member = User::factory()->create(['name' => 'قارئة نسيت', 'email' => 'forgot@example.test', 'recovery_phone' => '+966500000001']);
    PasswordHelpRequest::query()->create(['user_id' => $member->id]);

    $this->actingAs($admin)->get(route('admin.index'))
        ->assertSee('طلبات استعادة كلمة المرور')
        ->assertSee('قارئة نسيت')
        ->assertSee('forgot@example.test')
        ->assertSee('أنشئ الرابط وأرسله لرقمه');

    $this->actingAs($admin)->from(route('admin.index'))->post(route('admin.users.reset-link', $member))->assertRedirect(route('admin.index'));

    expect(PasswordHelpRequest::query()->open()->count())->toBe(0)
        ->and(session('resetLink')['whatsapp'])->toStartWith('https://wa.me/966500000001?text=');
    $this->actingAs($admin)->get(route('admin.index'))->assertDontSee('طلبات استعادة كلمة المرور');
});

it('lets the admin close a request without a link, and keeps members out', function () {
    $admin = tap(User::factory()->create(), fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    $help = PasswordHelpRequest::query()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->create())->post(route('admin.help-requests.close', $help))->assertForbidden();
    expect($help->fresh()->handled_at)->toBeNull();

    $this->actingAs($admin)->from(route('admin.index'))->post(route('admin.help-requests.close', $help))->assertRedirect(route('admin.index'));
    expect($help->fresh()->handled_at)->not->toBeNull();
});

it('sends the member on to the admin WhatsApp with a clear message that includes the email they typed', function () {
    noMailHost($this);
    User::factory()->create(['email' => 'forgot@example.test']);

    $this->from(route('password.request'))->post(route('password.email'), ['email' => ' Forgot@Example.test '])
        ->assertRedirect(route('password.request'));

    $url = session('whatsapp');
    expect($url)->toStartWith('https://wa.me/966595167827?text=');
    $message = rawurldecode(substr($url, strpos($url, '=') + 1));
    expect($message)->toBe("السلام عليكم، نسيت كلمة المرور في مِداد.\nبريد حسابي: forgot@example.test\nأرجو إرسال رابط استعادة كلمة المرور، وشكرًا لك.");

    $this->get(route('password.request'))->assertSee('data-whatsapp-open="https://wa.me/966595167827?text=', false)->assertSee('افتح واتساب');

    // An address without an account gets the same page and the same message shape: nothing tells them apart.
    $this->post(route('password.email'), ['email' => 'nobody@example.test']);
    expect(rawurldecode(substr(session('whatsapp'), strpos(session('whatsapp'), '=') + 1)))->toContain('بريد حسابي: nobody@example.test');
});
