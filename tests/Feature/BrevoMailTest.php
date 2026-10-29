<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

// MAIL_MAILER=brevo: mail goes to Brevo's HTTPS API (Render's free plan blocks SMTP). Brevo itself is replaced by a fake here.
function fakeBrevo(array &$sent): void
{
    config(['mail.default' => 'brevo', 'services.brevo.key' => 'xkeysib-test', 'mail.from.address' => 'midad@example.test', 'mail.from.name' => 'مِداد']);
    app()->instance('midad.brevo.http', new MockHttpClient(function (string $method, string $url, array $options) use (&$sent) {
        $sent[] = ['method' => $method, 'url' => $url, 'headers' => $options['headers'], 'body' => json_decode($options['body'], true)];

        return new MockResponse(json_encode(['messageId' => '<test@brevo>']), ['http_code' => 201]);
    }));
    Mail::purge('brevo');
}

it('sends through the Brevo API with the key, the sender and the recipient', function () {
    $sent = [];
    fakeBrevo($sent);

    Mail::raw('رسالة تجريبية', fn ($message) => $message->to('reader@example.test')->subject('مِداد'));

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['method'])->toBe('POST')
        ->and($sent[0]['url'])->toBe('https://api.brevo.com/v3/smtp/email')
        ->and(implode("\n", $sent[0]['headers']))->toContain('api-key: xkeysib-test')
        ->and($sent[0]['body']['sender']['email'])->toBe('midad@example.test')
        ->and($sent[0]['body']['to'][0]['email'])->toBe('reader@example.test')
        ->and($sent[0]['body']['subject'])->toBe('مِداد');
});

it('delivers the password reset email through Brevo', function () {
    $sent = [];
    fakeBrevo($sent);
    User::factory()->create(['email' => 'forgot@example.test']);

    $this->post(route('password.email'), ['email' => 'forgot@example.test'])->assertSessionHas('status');

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['body']['to'][0]['email'])->toBe('forgot@example.test')
        ->and($sent[0]['body']['htmlContent'])->toContain('reset-password');
});
