<?php

use App\Models\Quote;
use App\Models\User;

test('registration rejects bad data with Arabic messages shown next to the fields', function () {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->from(route('register'))->post(route('register.store'), [
        'name' => 'ع', 'email' => 'taken@example.test', 'password' => '1234567', 'password_confirmation' => '1234567',
    ])->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'name' => 'عدد أحرف الاسم يجب ألا يقل عن 2.',
            'email' => 'البريد الإلكتروني مستخدم من قبل.',
            'password' => 'عدد أحرف كلمة المرور يجب ألا يقل عن 8.',
        ]);

    $page = $this->get(route('register'))->assertOk();
    $page->assertSee('<small class="field-error" id="name-error" role="alert">عدد أحرف الاسم يجب ألا يقل عن 2.</small>', false)
        ->assertSee('<small class="field-error" id="email-error" role="alert">البريد الإلكتروني مستخدم من قبل.</small>', false)
        ->assertSee('<small class="field-error" id="password-error" role="alert">عدد أحرف كلمة المرور يجب ألا يقل عن 8.</small>', false);
    expect(User::query()->count())->toBe(1);
});

test('quotes of 2 and 156 characters are rejected and a valid quote is saved exactly once', function () {
    $reader = User::factory()->create();
    $this->actingAs($reader);

    $this->from(route('quotes.create'))->post(route('quotes.store'), ['text' => 'اب', 'category' => 'حياة'])
        ->assertSessionHasErrors(['text' => 'عدد أحرف نص الاقتباس يجب ألا يقل عن 3.']);
    $this->from(route('quotes.create'))->post(route('quotes.store'), ['text' => str_repeat('ب', 156), 'category' => 'حياة'])
        ->assertSessionHasErrors(['text' => 'عدد أحرف نص الاقتباس يجب ألا يزيد على 155.']);
    $this->post(route('quotes.store'), ['text' => 'كلمة صالحة للنشر.', 'category' => 'حياة'])->assertRedirect(route('home'));

    expect(Quote::query()->count())->toBe(1)
        ->and(Quote::query()->first()->user_id)->toBe($reader->id);
});

test('another user gets 403 on manual edit, update and delete requests', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $quote = Quote::query()->create(['user_id' => $owner->id, 'text' => 'نص يملكه صاحبه.', 'category' => 'أمل']);

    $this->actingAs($intruder);
    $this->get(route('quotes.edit', $quote))->assertForbidden();
    $this->put(route('quotes.update', $quote), ['text' => 'تعديل غير مسموح.', 'category' => 'أمل'])->assertForbidden();
    $this->delete(route('quotes.destroy', $quote))->assertForbidden();
    $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'text' => 'نص يملكه صاحبه.']);

    auth()->logout();
    $this->delete(route('quotes.destroy', $quote))->assertRedirect(route('login'));
});

test('guests see quote text, publisher name and publish date on both pages', function () {
    $publisher = User::factory()->create(['name' => 'ريم الكاتبة']);
    $quote = Quote::query()->create(['user_id' => $publisher->id, 'text' => 'الكلمة الطيبة صدقة.', 'category' => 'حياة']);

    foreach ([route('home'), route('quotes.index')] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('الكلمة الطيبة صدقة.')
            ->assertSee('ريم الكاتبة')
            ->assertSee($quote->created_at->format('Y/m/d'))
            ->assertSee('نُشر منذ');
    }
});

test('a new quote appears first on the home page and pushes the sixth off while staying in the library', function () {
    $oldest = Quote::query()->forceCreate(['text' => 'أقدم اقتباس هنا.', 'category' => 'تأمل', 'created_at' => now()->subHours(10)]);
    foreach (range(1, 4) as $number) {
        Quote::query()->forceCreate(['text' => "اقتباس سابق {$number}.", 'category' => 'تأمل', 'created_at' => now()->subHours(5 - $number)]);
    }
    expect($this->get(route('home'))->viewData('quotes')->pluck('id'))->toContain($oldest->id);

    $this->actingAs(User::factory()->create())
        ->post(route('quotes.store'), ['text' => 'أحدث اقتباس منشور.', 'category' => 'أمل']);

    $home = $this->get(route('home'))->viewData('quotes');
    expect($home)->toHaveCount(5)
        ->and($home->first()->text)->toBe('أحدث اقتباس منشور.')
        ->and($home->pluck('id'))->not->toContain($oldest->id);

    $library = $this->get(route('quotes.index'))->assertSee('أقدم اقتباس هنا.')->viewData('quotes');
    expect($library->total())->toBe(6)
        ->and($library->first()->text)->toBe('أحدث اقتباس منشور.');
});

test('pagination keeps search and category, and the clear-filters control is shown', function () {
    foreach (range(1, 13) as $number) {
        Quote::query()->forceCreate(['text' => "صبر جميل {$number}.", 'category' => 'أمل', 'created_at' => now()->subMinutes($number)]);
    }
    Quote::query()->create(['text' => 'صبر في تصنيف آخر.', 'category' => 'حب']);

    $page = $this->get(route('quotes.index', ['q' => 'صبر', 'category' => 'أمل']))->assertOk()
        ->assertSee('مسح البحث والفلاتر')
        ->assertSee('عرض 1–12 من 13')
        ->assertDontSee('صبر في تصنيف آخر.');
    $nextUrl = $page->viewData('quotes')->nextPageUrl();
    expect($nextUrl)->toContain('category=')->toContain('q=');
    $this->get($nextUrl)->assertOk()->assertSee('صبر جميل 13.');

    $this->get(route('quotes.index', ['q' => 'لا يوجد شيء كهذا']))->assertOk()->assertSee('لا توجد نتائج مطابقة.');
});

test('like and bookmark state is shown the same way on the home page and the quotes page', function () {
    $reader = User::factory()->create();
    $quote = Quote::query()->create(['text' => 'اقتباس للتفاعل.', 'category' => 'حب']);

    $this->actingAs($reader)->postJson(route('quotes.like', $quote))->assertOk()->assertJson(['liked' => true, 'likes_count' => 1]);
    $this->post(route('quotes.favorite', $quote))->assertRedirect();

    foreach ([route('home'), route('quotes.index')] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('like-button is-liked', false)
            ->assertSee('icon-button is-favorited', false);
    }

    $this->postJson(route('quotes.like', $quote))->assertJson(['liked' => false, 'likes_count' => 0]);
    $this->assertDatabaseCount('quote_likes', 0);
});

test('legacy quotes longer than 155 characters are kept intact and fully readable', function () {
    $long = str_repeat('قِفا نَبكِ مِن ذِكرى حَبيبٍ وَمَنزِلِ ', 6);
    $quote = Quote::query()->create(['text' => $long, 'category' => 'تأمل']);

    expect(mb_strlen($long))->toBeGreaterThan(155);
    $this->get(route('quotes.show', $quote))->assertOk()->assertSee(trim($long));
    $this->get(route('quotes.index'))->assertOk()->assertSee('قراءة المزيد');
    expect($quote->fresh()->text)->toBe($long);
});

test('every quote listing renders the single shared quote-card component', function () {
    $reader = User::factory()->create();
    $quote = Quote::query()->create(['user_id' => $reader->id, 'text' => 'بطاقة واحدة لكل الصفحات.', 'author' => 'المتنبي', 'category' => 'تأمل']);
    $this->actingAs($reader)->post(route('quotes.favorite', $quote));

    foreach ([route('home'), route('quotes.index'), route('favorites'), route('profile.show', $reader), route('authors.show', ['author' => 'المتنبي'])] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('quote-card-publisher', false)
            ->assertSee('بطاقة واحدة لكل الصفحات.');
    }
});

test('every page view extends the shared layout', function () {
    $views = collect(\Illuminate\Support\Facades\File::allFiles(resource_path('views')))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->reject(fn ($file) => preg_match('#^(components|mail|pagination|layouts)(/|$)#', str_replace('\\', '/', $file->getRelativePath())));

    expect($views)->not->toBeEmpty();
    foreach ($views as $file) {
        expect(file_get_contents($file->getPathname()))->toContain("@extends('layouts.app')");
    }
});

test('the owner of a legacy long quote sees a length notice on the edit form', function () {
    $owner = User::factory()->create();
    $quote = Quote::query()->create(['user_id' => $owner->id, 'text' => str_repeat('كلمة طويلة ', 20), 'category' => 'حياة']);

    $this->actingAs($owner)->get(route('quotes.edit', $quote))->assertOk()
        ->assertSee('اختصر النص إلى 155 حرفًا أو أقل');
});

test('likes and bookmarks toggle, appear on the favorites page and are unique per user at the database level', function () {
    $reader = User::factory()->create();
    $quote = Quote::query()->create(['text' => 'اقتباس يستحق الحفظ.', 'category' => 'أمل']);
    $this->actingAs($reader);

    $this->post(route('quotes.favorite', $quote))->assertSessionHas('status', 'حُفظ الاقتباس في محفوظاتك.');
    $this->get(route('favorites'))->assertOk()->assertSee('اقتباس يستحق الحفظ.');
    expect($reader->favorites()->count())->toBe(1)->and($reader->favorites()->first()->quote->is($quote))->toBeTrue();

    $this->post(route('quotes.favorite', $quote))->assertSessionHas('status', 'أُزيل الاقتباس من محفوظاتك.');
    $this->get(route('favorites'))->assertDontSee('اقتباس يستحق الحفظ.');

    $reader->likes()->create(['quote_id' => $quote->id]);
    expect(fn () => $reader->likes()->create(['quote_id' => $quote->id]))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
    $reader->favorites()->create(['quote_id' => $quote->id]);
    expect(fn () => $reader->favorites()->create(['quote_id' => $quote->id]))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);

    $quote->delete();
    $this->assertDatabaseCount('quote_likes', 0);
    $this->assertDatabaseCount('quote_favorites', 0);
});
