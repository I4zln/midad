<?php

use App\Models\Quote;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

// Recovery without email: an optional WhatsApp number only the admin sees, the owner told about every reset link,
// and other devices signed out after the password changes.

it('saves an optional recovery number in one format and rejects one it cannot read', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'recovery_phone' => '059 516 7827'])->assertSessionHasNoErrors();
    expect($user->fresh()->recovery_phone)->toBe('+966595167827');

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'recovery_phone' => '12ab'])->assertSessionHasErrors('recovery_phone');
    expect($user->fresh()->recovery_phone)->toBe('+966595167827');

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'recovery_phone' => ''])->assertSessionHasNoErrors();
    expect($user->fresh()->recovery_phone)->toBeNull();
});

it('keeps the recovery number private: not on the profile, not in serialised data', function () {
    $user = User::factory()->create(['recovery_phone' => '+966595167827']);

    $this->get(route('profile.show', $user))->assertOk()->assertDontSee('595167827');
    expect($user->toArray())->not->toHaveKey('recovery_phone');
});

it('shows the admin what to check before sending a link', function () {
    $admin = tap(User::factory()->create(), fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    $member = User::factory()->create(['name' => 'صاحب الحساب', 'username' => 'owner1', 'recovery_phone' => '+966511112222']);
    Quote::query()->create(['user_id' => $member->id, 'text' => 'آخر ما كتبه صاحب الحساب', 'category' => 'أمل']);

    $this->actingAs($admin)->from(route('admin.index'))->post(route('admin.users.reset-link', $member));
    $this->actingAs($admin)->get(route('admin.index'))
        ->assertOk()
        ->assertSee('رقم الاستعادة')
        ->assertSee('+966511112222')
        ->assertSee('@owner1')
        ->assertSee('آخر ما كتبه صاحب الحساب')
        ->assertSee('https://wa.me/966511112222?text=', false)
        ->assertSee('أرسل على واتساب إلى رقمه');
});

it('tells the owner when a reset link is created for their account', function () {
    $admin = tap(User::factory()->create(), fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    $member = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.users.reset-link', $member));

    $notice = UserNotification::query()->where('user_id', $member->id)->first();
    expect($notice?->type)->toBe('security')->and($notice->actor_id)->toBe($admin->id)->and($notice->message)->toContain('إن لم تطلبه');
});

it('signs the account out on other devices after the password is reset', function () {
    config(['session.driver' => 'database']);
    $member = User::factory()->create(['email' => 'owner@example.test']);
    DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $member->id, 'ip_address' => '10.0.0.9', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);
    $token = Password::broker()->createToken($member);

    $this->post(route('password.update'), ['token' => $token, 'email' => 'owner@example.test', 'password' => 'A-new-pass-2026!', 'password_confirmation' => 'A-new-pass-2026!'])
        ->assertSessionHasNoErrors();

    expect(DB::table('sessions')->where('id', 'other-device')->exists())->toBeFalse();
});

it('accepts an animated GIF as the profile cover and keeps it as uploaded', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $user = User::factory()->create();
    // A two-frame animated GIF, built by hand so the test shows the file is stored byte for byte.
    $gif = base64_decode('R0lGODlhAgACAPAAAAAAAP///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQACgAAACwAAAAAAgACAAACAoRRACH5BAAKAAAALAAAAAACAAIAAAIChFEAOw==');
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('cover.gif', $gif);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'cover' => $file])->assertSessionHasNoErrors();

    $path = $user->fresh()->cover_path;
    expect($path)->toEndWith('.gif');
    expect(\Illuminate\Support\Facades\Storage::disk('public')->get($path))->toBe($gif);
});
