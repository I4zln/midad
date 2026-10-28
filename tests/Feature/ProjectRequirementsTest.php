<?php

use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('home shows exactly the five most recently published quotes while the library is paginated', function () {
    $published = [];
    foreach (range(1, 14) as $number) {
        $published[] = Quote::query()->forceCreate([
            'text' => "اقتباس رقم {$number} للاختبار.",
            'category' => 'تأمل',
            'created_at' => now()->subMinutes(20 - $number),
            'updated_at' => now()->subMinutes(20 - $number),
        ]);
    }

    $homeResponse = $this->get(route('home'))->assertOk();
    $homeQuotes = $homeResponse->viewData('quotes');
    expect($homeQuotes)->toHaveCount(5)
        ->and($homeQuotes->pluck('id')->all())->toBe(collect($published)->reverse()->take(5)->pluck('id')->all());
    expect(substr_count($homeResponse->getContent(), 'class="quote-card '))->toBe(5);

    $page = $this->get(route('quotes.index', ['q' => 'اقتباس رقم', 'category' => 'تأمل']))->assertOk();
    $paginator = $page->viewData('quotes');
    parse_str(parse_url($paginator->url(2), PHP_URL_QUERY), $pageQuery);
    expect($paginator->total())->toBe(14)
        ->and($paginator->perPage())->toBe(12)
        ->and($pageQuery)->toMatchArray(['q' => 'اقتباس رقم', 'category' => 'تأمل']);
    $page->assertSee('اقتباس رقم 14 للاختبار.');
    $secondPage = $this->get($paginator->url(2))->assertOk()->viewData('quotes');
    expect($secondPage->count())->toBe(2)
        ->and($secondPage->total())->toBe(14);
});

test('registration validates name length, unique valid email, and password length and hashes passwords', function () {
    $this->post(route('register.store'), [
        'name' => 'ع', 'email' => 'short-name@example.test', 'password' => 'valid-pass-123', 'password_confirmation' => 'valid-pass-123',
    ])->assertSessionHasErrors('name');
    $this->post(route('register.store'), [
        'name' => str_repeat('ن', 51), 'email' => 'long-name@example.test', 'password' => 'valid-pass-123', 'password_confirmation' => 'valid-pass-123',
    ])->assertSessionHasErrors('name');

    User::factory()->create(['email' => 'taken@example.test']);
    $this->post(route('register.store'), [
        'name' => 'قارئ جديد', 'email' => 'taken@example.test', 'password' => 'valid-pass-123', 'password_confirmation' => 'valid-pass-123',
    ])->assertSessionHasErrors('email');

    $this->post(route('register.store'), [
        'name' => 'قارئ جديد', 'email' => 'new-reader@example.test', 'password' => '1234567', 'password_confirmation' => '1234567',
    ])->assertSessionHasErrors('password');

    $this->post(route('register.store'), [
        'name' => 'قارئ جديد', 'email' => 'new-reader@example.test', 'password' => 'secure-pass-123', 'password_confirmation' => 'secure-pass-123',
    ])->assertRedirect(route('quotes.create'));
    $user = User::query()->where('email', 'new-reader@example.test')->firstOrFail();
    expect(Hash::check('secure-pass-123', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('secure-pass-123');
});

test('quote creation enforces the 3 to 155 character range and requires a signed-in reader', function () {
    $payload = ['category' => 'حياة'];
    $this->post(route('quotes.store'), $payload + ['text' => 'م'])->assertRedirect(route('login'));

    $reader = User::factory()->create();
    $this->actingAs($reader)->from(route('quotes.create'))
        ->post(route('quotes.store'), $payload + ['text' => 'اب'])
        ->assertSessionHasErrors('text');
    $this->from(route('quotes.create'))
        ->post(route('quotes.store'), $payload + ['text' => str_repeat('ا', 156)])
        ->assertSessionHasErrors('text');
    $this->from(route('quotes.create'))
        ->post(route('quotes.store'), $payload + ['text' => str_repeat('ا', 155)])
        ->assertRedirect(route('home'));
    $this->assertDatabaseHas('quotes', ['user_id' => $reader->id, 'text' => str_repeat('ا', 155)]);
});

test('only a quote owner can update or delete it and owners can use both actions', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $quote = Quote::query()->create(['user_id' => $owner->id, 'text' => 'كلمات محفوظة هنا.', 'category' => 'حياة']);

    $this->actingAs($other)->put(route('quotes.update', $quote), ['text' => 'محاولة تعديل.', 'category' => 'حياة'])->assertForbidden();
    $this->delete(route('quotes.destroy', $quote))->assertForbidden();
    $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'text' => 'كلمات محفوظة هنا.']);

    $this->actingAs($owner)->put(route('quotes.update', $quote), ['text' => 'تعديل صحيح.', 'category' => 'حياة'])->assertRedirect(route('quotes.show', $quote));
    $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'text' => 'تعديل صحيح.']);
    $this->from(route('quotes.edit', $quote))->put(route('quotes.update', $quote), ['text' => str_repeat('ا', 156), 'category' => 'حياة'])->assertSessionHasErrors('text');
    // Deleted from its own edit page → back to the owner's quotes.
    $this->delete(route('quotes.destroy', $quote))->assertRedirect(route('profile.show', $owner));
    $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
});

test('likes and saved quotes remain database-backed community features', function () {
    $reader = User::factory()->create();
    $quote = Quote::query()->create(['text' => 'هذه كلمة جميلة.', 'category' => 'حب']);

    $this->actingAs($reader)->post(route('quotes.like', $quote))->assertRedirect();
    $this->post(route('quotes.favorite', $quote))->assertRedirect();
    $this->assertDatabaseHas('quote_likes', ['quote_id' => $quote->id, 'user_id' => $reader->id]);
    $this->assertDatabaseHas('quote_favorites', ['quote_id' => $quote->id, 'user_id' => $reader->id]);
});
