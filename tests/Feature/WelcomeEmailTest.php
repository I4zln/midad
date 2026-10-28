<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

$newMember = fn (array $overrides = []) => array_merge([
    'name' => 'قارئ جديد', 'email' => 'new-member@example.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
], $overrides);

test('Registered has a single listener: the welcome email (no automatic verification mail)', function () {
    expect(Event::hasListeners(Registered::class))->toBeTrue();
    expect(app('events')->getRawListeners()[Registered::class])->toBe([\App\Listeners\SendWelcomeEmail::class]);
});

test('registering sends exactly one welcome email to the new member and nothing else', function () use ($newMember) {
    Mail::fake();
    Notification::fake();

    $this->post(route('register.store'), $newMember())->assertRedirect(route('quotes.create'));

    Mail::assertSent(WelcomeMail::class, 1);
    Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail) => $mail->hasTo('new-member@example.test'));
    Notification::assertNothingSent();
});

test('a mail failure never blocks registration and is logged without addresses or SMTP details', function () use ($newMember) {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Failed to authenticate on SMTP server with username "sender@gmail.com"'));
    Log::spy();

    $this->post(route('register.store'), $newMember())
        ->assertRedirect(route('quotes.create'))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'new-member@example.test')->exists())->toBeTrue();
    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) {
        return $message === 'Midad welcome email could not be delivered.'
            && $context['error'] === 'RuntimeException'
            && ! str_contains(json_encode($context, JSON_UNESCAPED_UNICODE), '@');
    });
});

test('the welcome email has the right sender, subject, verse, slogan, buttons and an escaped name', function () {
    $user = new User(['name' => '<b>سارة & ليلى</b>', 'email' => 'reader@example.test', 'password' => 'never-shown-123']);
    $mail = new WelcomeMail($user);

    $mail->assertHasSubject('أهلًا بك في مِداد');
    $mail->assertFrom((string) config('mail.from.address'), 'مِداد | MIDAD');
    $mail->assertSeeInHtml('dir="rtl"', false);
    $mail->assertSeeInHtml('أهلًا بك يا &lt;b&gt;سارة &amp; ليلى&lt;/b&gt;', false);
    $mail->assertDontSeeInHtml('<b>سارة', false);
    $mail->assertSeeInOrderInHtml([
        '«أهلًا بمَن جاءَ الحروفَ مُحِبًّا … فالحرفُ بيتٌ والمِدادُ رِحابُ»',
        'href="'.route('quotes.index').'"', 'تصفّح الاقتباسات',
        'href="'.route('quotes.create').'"', '+ شارك أول اقتباس لك',
        'لأن للكلمات أثرًا يبقى',
    ], false);
    $mail->assertSeeInText('أهلًا بمَن جاءَ الحروفَ مُحِبًّا … فالحرفُ بيتٌ والمِدادُ رِحابُ');
    $mail->assertSeeInText(route('quotes.create'));
    $mail->assertDontSeeInHtml('never-shown-123');
    $mail->assertDontSeeInText('never-shown-123');
});

test('the welcome-test command sends to runtime addresses only', function () {
    Mail::fake();

    $this->artisan('midad:welcome-test', ['to' => ['one@example.test', 'two@example.test']])->assertExitCode(0);

    Mail::assertSent(WelcomeMail::class, 2);
    Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('one@example.test'));
    Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('two@example.test'));
});
