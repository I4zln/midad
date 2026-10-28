<?php

use App\Models\Quote;
use App\Models\User;

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('أحدث')
        ->assertSee('أهلًا بك في مِـداد')
        ->assertSee('MIDAD')
        ->assertSee('أبو الطيب المتنبي')
        ->assertSee('ethraa-audio-volume');
});

test('the poet directory only lists names attached to saved quotes', function () {
    Quote::query()->create(['text' => 'بيت محفوظ من المكتبة.', 'author' => 'أبو الطيب المتنبي', 'category' => 'تأمل']);

    $this->get(route('poets.index'))
        ->assertOk()
        ->assertSee('أبو الطيب المتنبي')
        ->assertSee('اقتباس محفوظ');
});

test('the shared audio player is present across literary and profile pages', function () {
    $user = User::factory()->create();
    $quote = Quote::query()->create(['text' => 'نص محفوظ.', 'author' => 'كاتب المكتبة', 'category' => 'حياة']);
    $pages = [
        route('home'), route('about'), route('quotes.index'), route('poets.index'), route('muallaqat.index'),
        route('quotes.show', $quote), route('authors.show', ['author' => 'كاتب المكتبة']),
        route('profile.show', $user),
    ];

    foreach ($pages as $page) {
        $this->get($page)->assertOk()
            ->assertSee('ethraa-audio-toggle')
            ->assertSee('ethraa-audio-volume')
            ->assertSee('audio/mahmoud-darwish.mp3');
    }
});

test('a reader can save a quote to the library', function () {
    $reader = User::factory()->create();
    $response = $this->actingAs($reader)->post(route('quotes.store'), [
        'text' => 'تبقى الكلمات حين تمنحنا معنى.',
        'author' => 'كاتب',
        'source' => 'دفتر',
        'category' => 'تأمل',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertDatabaseHas('quotes', [
        'text' => 'تبقى الكلمات حين تمنحنا معنى.',
        'author' => 'كاتب',
        'category' => 'تأمل',
    ]);
    $this->get(route('home'))->assertSee('تبقى الكلمات حين تمنحنا معنى.');
});

test('quote submissions are validated', function () {
    $response = $this->actingAs(User::factory()->create())->from('/')
        ->post(route('quotes.store'), [
            'text' => '',
            'category' => 'غير معروف',
        ]);

    $response->assertRedirect('/')
        ->assertSessionHasErrors(['text', 'category']);
    $this->assertDatabaseCount('quotes', 0);
});

test('the creator page includes developer links and the muallaqat page is public', function () {
    $this->get(route('creator'))
        ->assertOk()
        ->assertSee('عبدالعزيز المطرفي')
        ->assertSee('https://www.instagram.com/i1zln/')
        ->assertSee('https://github.com/I4zln')
        ->assertSee('https://www.linkedin.com/in/abdulaziz-al-matrafi-bbb238358/')
        ->assertSee('noopener noreferrer');

    $this->get(route('muallaqat.index'))
        ->assertOk()
        ->assertSee('المعلّقات السبع')
        ->assertSee('من ذاكرة القصيدة العربية');
});

test('a visitor can open quote and author pages from the library', function () {
    $quote = Quote::query()->create([
        'text' => 'الكلمة تعبر وتبقى.',
        'author' => 'كاتب المكتبة',
        'source' => 'دفتر',
        'category' => 'تأمل',
    ]);

    $this->get(route('quotes.show', $quote))
        ->assertOk()
        ->assertSee('الكلمة تعبر وتبقى.')
        ->assertSee(route('authors.show', ['author' => 'كاتب المكتبة']));

    $this->get(route('authors.show', ['author' => 'كاتب المكتبة']))
        ->assertOk()
        ->assertSee('كاتب المكتبة')
        ->assertSee('الكلمة تعبر وتبقى.');
});

test('search and category filters return matching quotes', function () {
    Quote::query()->create(['text' => 'تفصيل يضيء اليوم.', 'author' => 'كاتب أ', 'category' => 'حياة']);
    Quote::query()->create(['text' => 'هدوء المساء.', 'author' => 'كاتب ب', 'category' => 'تأمل']);

    $this->get(route('quotes.index', ['q' => 'يضيء']))
        ->assertOk()
        ->assertSee('تفصيل يضيء اليوم.')
        ->assertDontSee('هدوء المساء.');

    $this->get(route('quotes.index', ['category' => 'تأمل']))
        ->assertOk()
        ->assertSee('هدوء المساء.')
        ->assertDontSee('تفصيل يضيء اليوم.');
});

test('community actions require an account and persist likes comments follows and notifications', function () {
    $author = User::factory()->create();
    $reader = User::factory()->create();
    $quote = Quote::query()->create([
        'user_id' => $author->id,
        'text' => 'كل كلمة نافذة.',
        'category' => 'حياة',
    ]);

    $this->post(route('quotes.like', $quote))->assertRedirect(route('login'));
    $this->actingAs($reader)->post(route('quotes.like', $quote))->assertRedirect();
    $this->assertDatabaseHas('quote_likes', ['quote_id' => $quote->id, 'user_id' => $reader->id]);
    $this->assertDatabaseHas('user_notifications', ['user_id' => $author->id, 'type' => 'like']);
    $this->post(route('quotes.like', $quote))->assertRedirect();
    $this->assertDatabaseMissing('quote_likes', ['quote_id' => $quote->id, 'user_id' => $reader->id]);

    $this->post(route('quotes.favorite', $quote))->assertRedirect();
    $this->assertDatabaseHas('quote_favorites', ['quote_id' => $quote->id, 'user_id' => $reader->id]);
    $this->post(route('quotes.favorite', $quote))->assertRedirect();
    $this->assertDatabaseMissing('quote_favorites', ['quote_id' => $quote->id, 'user_id' => $reader->id]);
    $this->post(route('quotes.favorite', $quote))->assertRedirect();

    $this->post(route('quotes.comments.store', $quote), ['body' => 'كلمات لطيفة.'])->assertRedirect(route('quotes.show', $quote));
    $this->assertDatabaseHas('quote_comments', ['quote_id' => $quote->id, 'user_id' => $reader->id, 'body' => 'كلمات لطيفة.']);

    $this->post(route('users.follow', $author))->assertRedirect();
    $this->assertDatabaseHas('user_follows', ['follower_id' => $reader->id, 'followed_id' => $author->id]);
    $this->assertDatabaseHas('user_notifications', ['user_id' => $author->id, 'type' => 'follow']);
    $this->post(route('users.follow', $author))->assertRedirect();
    $this->assertDatabaseMissing('user_follows', ['follower_id' => $reader->id, 'followed_id' => $author->id]);
    $this->post(route('users.follow', $author))->assertRedirect();

    $this->get(route('favorites'))->assertOk()->assertSee('كل كلمة نافذة.');
    $this->actingAs($author);
    $this->get(route('notifications'))->assertOk()->assertSee('بدأ بمتابعتك.');
    $this->post(route('notifications.read'))->assertRedirect();
    $this->assertDatabaseMissing('user_notifications', ['user_id' => $author->id, 'read_at' => null]);
});

test('readers can register and access their linked profile', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'قارئ أثر',
        'email' => 'reader@example.test',
        'password' => 'secure-pass-123',
        'password_confirmation' => 'secure-pass-123',
    ]);

    $user = User::query()->where('email', 'reader@example.test')->firstOrFail();
    $response->assertRedirect(route('quotes.create'));
    $this->assertAuthenticatedAs($user);
    $this->get(route('profile.show', $user))->assertOk()->assertSee('قارئ أثر');
});

test('readers can sign in and sign out', function () {
    $user = User::factory()->create([
        'email' => 'login-reader@example.test',
        'password' => bcrypt('reader-password-123'),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'reader-password-123',
    ])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();
});
