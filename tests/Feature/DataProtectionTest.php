<?php

use App\Models\Quote;
use App\Models\QuoteComment;
use App\Models\QuoteFavorite;
use App\Models\QuoteLike;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Support\Facades\DB;

test('a member can delete their account and all their data, after confirming the password', function () {
    $user = User::factory()->create(['password' => 'secret-pass-1']);
    $other = User::factory()->create();
    $own = Quote::query()->forceCreate(['text' => 'اقتباسي', 'category' => 'حياة', 'user_id' => $user->id]);
    $others = Quote::query()->forceCreate(['text' => 'اقتباس غيري', 'category' => 'حياة', 'user_id' => $other->id]);
    QuoteLike::query()->create(['quote_id' => $others->id, 'user_id' => $user->id]);
    QuoteLike::query()->create(['quote_id' => $own->id, 'user_id' => $other->id]);
    QuoteFavorite::query()->create(['quote_id' => $others->id, 'user_id' => $user->id]);
    QuoteComment::query()->forceCreate(['quote_id' => $others->id, 'user_id' => $user->id, 'body' => 'تعليق']);
    UserFollow::query()->create(['follower_id' => $user->id, 'followed_id' => $other->id]);

    $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'wrong-pass-1'])->assertSessionHasErrors('password');
    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();

    $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'secret-pass-1'])->assertRedirect(route('home'));
    $this->assertGuest();
    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Quote::query()->whereKey($own->id)->exists())->toBeFalse()
        ->and(Quote::query()->whereKey($others->id)->exists())->toBeTrue()
        ->and(QuoteLike::query()->where('user_id', $user->id)->orWhere('quote_id', $own->id)->count())->toBe(0)
        ->and(QuoteFavorite::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(QuoteComment::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(UserFollow::query()->where('follower_id', $user->id)->count())->toBe(0)
        ->and($other->fresh())->not->toBeNull();
});

test('guests cannot delete accounts, and is_admin cannot be mass-assigned', function () {
    $this->delete(route('account.destroy'), ['password' => 'x'])->assertRedirect(route('login'));

    $user = new User(['name' => 'قارئ', 'email' => 'x@example.test', 'password' => 'secret-pass-1', 'is_admin' => true]);
    expect($user->is_admin)->toBeNull();
});

test('new passwords need letters and numbers', function () {
    $this->post(route('register.store'), ['name' => 'قارئ', 'email' => 'new@example.test', 'password' => 'onlyletters', 'password_confirmation' => 'onlyletters'])
        ->assertSessionHasErrors('password');
    $this->post(route('register.store'), ['name' => 'قارئ', 'email' => 'new@example.test', 'password' => '12345678', 'password_confirmation' => '12345678'])
        ->assertSessionHasErrors('password');
    $this->post(route('register.store'), ['name' => 'قارئ', 'email' => 'new@example.test', 'password' => 'letters-and-7', 'password_confirmation' => 'letters-and-7'])
        ->assertSessionHasNoErrors();
});

test('responses carry a strict CSP and the inline scripts carry its nonce', function () {
    $response = $this->get(route('home'))->assertOk();
    $policy = $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([^']+)'/", $policy, $match);

    expect($policy)->toContain("default-src 'self'")->toContain("object-src 'none'")->toContain("frame-ancestors 'self'")
        ->and($match[1] ?? null)->not->toBeNull()
        ->and($response->getContent())->toContain('nonce="'.$match[1].'"')
        ->and($response->getContent())->not->toContain('onerror=');
});

test('error pages are Arabic and reveal no technical details', function () {
    $this->get('/no-such-page-here')->assertNotFound()->assertSee('الصفحة غير موجودة')->assertDontSee('Symfony')->assertDontSee('stack');
});

test('public pages and JSON never expose other members\' email addresses', function () {
    $author = User::factory()->create(['email' => 'hidden-author@example.test']);
    $quote = Quote::query()->forceCreate(['text' => 'كلمة', 'category' => 'حياة', 'user_id' => $author->id]);

    foreach ([route('home'), route('quotes.index'), route('quotes.show', $quote), route('profile.show', $author)] as $url) {
        $this->get($url)->assertOk()->assertDontSee('hidden-author@example.test');
    }
    $this->actingAs(User::factory()->create())->postJson(route('quotes.like', $quote))->assertOk()->assertDontSee('hidden-author@example.test');
    expect($author->toArray())->not->toHaveKey('email');
});

test('the privacy page explains what is stored and links to account deletion', function () {
    $this->get(route('privacy'))->assertOk()->assertSee('ماذا نحفظ عنك')->assertSee('حذف الحساب');
    $this->get(route('register'))->assertSee(route('privacy'), false);
});
