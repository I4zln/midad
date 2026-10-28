<?php

test('every response carries the baseline security headers', function () {
    $this->get(route('home'))->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security'); // not production, not https
});

test('production redirects http to https with 301 and sends HSTS over https', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.force_https' => true]);

    $this->get('http://localhost/quotes?page=2')->assertStatus(301)->assertRedirect('https://localhost/quotes?page=2');

    // Behind a TLS-terminating proxy (Cloudflare, a host's load balancer): no loop, HSTS present.
    $this->get('http://localhost/quotes', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('production answers the plain-http health check on /up instead of redirecting', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.force_https' => true]);

    $this->get('http://localhost/up')->assertOk();
});

test('production with FORCE_HTTPS=false serves plain http (local production-mode testing)', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.force_https' => false]);

    $this->get('http://localhost/quotes')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
});

test('session cookie is secure, http-only and same-site when SESSION_SECURE_COOKIE is on', function () {
    config(['session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax']);

    $cookie = collect($this->get(route('login'))->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));
    expect($cookie)->not->toBeNull()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax');
});

test('login, register and forgot-password are rate limited', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.store'), []);
    }
    $this->post(route('register.store'), [])->assertStatus(429);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.email'), ['email' => "x{$attempt}@example.test"]);
    }
    $this->post(route('password.email'), ['email' => 'x@example.test'])->assertStatus(429);

    foreach (range(1, 10) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nobody@example.test', 'password' => 'x']);
    }
    $this->post(route('login.store'), ['email' => 'nobody@example.test', 'password' => 'x'])->assertStatus(429);
});
