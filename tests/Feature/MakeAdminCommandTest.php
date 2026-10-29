<?php

use App\Models\User;

it('gives an existing account the admin role', function () {
    $user = User::factory()->create(['email' => 'Reader@Example.test', 'is_admin' => false]);

    $this->artisan('midad:make-admin', ['email' => ' reader@example.test '])->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeTrue();
});

it('leaves everything as it is when no account has that email', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->artisan('midad:make-admin', ['email' => 'nobody@example.test'])->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeFalse();
});
