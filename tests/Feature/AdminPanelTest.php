<?php

use App\Models\Quote;
use App\Models\User;

function makeAdmin(): User
{
    return tap(User::factory()->create(['username' => 'midad-admin']), fn (User $user) => $user->forceFill(['is_admin' => true])->save());
}

it('is only for admins', function () {
    $this->get(route('admin.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.index'))->assertForbidden();
});

it('shows the numbers, the quotes, the most liked and the newest members', function () {
    $admin = makeAdmin();
    $member = User::factory()->create(['name' => 'قارئة جديدة']);
    Quote::query()->create(['user_id' => $member->id, 'text' => 'الكلمة الطيبة صدقة', 'author' => 'مأثور', 'category' => 'حياة']);

    $this->actingAs($admin)->get(route('admin.index'))
        ->assertOk()
        ->assertSee('@midad-admin')
        ->assertDontSee('{{', false)
        ->assertSeeInOrder(['الاقتباسات', 'الأعضاء', 'الإعجابات', 'جديد هذا الأسبوع'])
        ->assertSee('الكلمة الطيبة صدقة')
        ->assertSee('نشره قارئة جديدة')
        ->assertSee('الأكثر إعجابًا')
        ->assertSee('أحدث الأعضاء');
});

it('searches the text, the author and the publisher, and filters by category', function () {
    $admin = makeAdmin();
    $publisher = User::factory()->create(['name' => 'سلمى']);
    Quote::query()->create(['user_id' => $publisher->id, 'text' => 'نص عن البحر', 'author' => 'جبران', 'category' => 'تأمل']);
    Quote::query()->create(['user_id' => $admin->id, 'text' => 'نص عن الصحراء', 'author' => 'المتنبي', 'category' => 'حياة']);

    // The side lists show every quote, so check the searched list itself.
    $found = fn (array $query) => $this->actingAs($admin)->get(route('admin.index', $query))->assertOk()->viewData('quotes')->pluck('text')->all();

    expect($found(['q' => 'البحر']))->toBe(['نص عن البحر'])
        ->and($found(['q' => 'المتنبي']))->toBe(['نص عن الصحراء'])
        ->and($found(['q' => 'سلمى']))->toBe(['نص عن البحر'])
        ->and($found(['category' => 'حياة']))->toBe(['نص عن الصحراء'])
        ->and($found(['category' => 'غير موجود']))->toHaveCount(2);
    $this->actingAs($admin)->get(route('admin.index', ['q' => 'لا يوجد']))->assertSee('لا توجد اقتباسات مطابقة');
});

it('lets the admin delete any quote', function () {
    $admin = makeAdmin();
    $quote = Quote::query()->create(['user_id' => User::factory()->create()->id, 'text' => 'اقتباس سيُحذف', 'category' => 'أمل']);

    $this->actingAs($admin)->from(route('admin.index'))->delete(route('admin.quotes.destroy', $quote))
        ->assertRedirect(route('admin.index'))->assertSessionHas('status');

    expect(Quote::query()->whereKey($quote->id)->exists())->toBeFalse();
});

it('gives the admin a one-time reset link that lets the member choose a new password', function () {
    $admin = makeAdmin();
    $member = User::factory()->create(['name' => 'عضو نسي كلمته', 'email' => 'forgot@example.test']);

    $this->actingAs($admin)->get(route('admin.index', ['member' => 'forgot@']))->assertSee('forgot@example.test')->assertSee('رابط استعادة');

    $response = $this->actingAs($admin)->from(route('admin.index'))->post(route('admin.users.reset-link', $member));
    $response->assertRedirect(route('admin.index'));
    $link = session('resetLink');
    expect($link['name'])->toBe('عضو نسي كلمته')->and($link['url'])->toContain('/reset-password/')->and($link['minutes'])->toBe(60);

    $this->get(route('admin.index'))->assertSee('رابط استعادة كلمة المرور لـ عضو نسي كلمته')->assertSee($link['url'], false);

    // The member, signed out, follows the link and chooses a new password.
    auth()->logout();
    $this->get($link['url'])->assertOk();
    parse_str(parse_url($link['url'], PHP_URL_QUERY), $query);
    $token = basename(parse_url($link['url'], PHP_URL_PATH));
    $this->post(route('password.update'), ['token' => $token, 'email' => $query['email'], 'password' => 'A-new-pass-2026!', 'password_confirmation' => 'A-new-pass-2026!'])
        ->assertSessionHasNoErrors();

    expect(\Illuminate\Support\Facades\Hash::check('A-new-pass-2026!', $member->fresh()->password))->toBeTrue();
});

it('does not let members create reset links', function () {
    $member = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($member)->post(route('admin.users.reset-link', $other))->assertForbidden();
    $this->post(route('admin.users.reset-link', $other));
    expect(\Illuminate\Support\Facades\DB::table('password_reset_tokens')->count())->toBe(0);
});

it('apologises on «نسيت كلمة المرور» only while email cannot be sent', function () {
    $apology = 'نعتذر، إرسال الرسائل إلى البريد متوقف مؤقتًا';

    $this->get(route('password.request'))->assertOk()->assertDontSee($apology)->assertDontSee('لم تصلك الرسالة؟');

    app()->detectEnvironment(fn () => 'production');
    config(['mail.default' => 'log', 'app.force_https' => false]);
    $this->get(route('password.request'))->assertOk()->assertSee($apology)->assertSee('وسنساعدك على اختيار كلمة مرور جديدة');
});
