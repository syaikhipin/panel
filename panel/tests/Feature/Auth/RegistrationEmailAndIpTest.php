<?php

use App\Models\User;

it('fills the registration email when a user is created', function () {
    $user = User::factory()->create(['email' => 'dennis@example.com']);

    expect($user->registration_email)->toBe('dennis@example.com');
});

it('never changes the registration email after creation', function () {
    $user = User::factory()->create(['email' => 'dennis@example.com']);

    $user->update(['email' => 'new@example.com']);
    $user->registration_email = 'hacked@example.com';
    $user->save();

    expect($user->fresh())
        ->email->toBe('new@example.com')
        ->registration_email->toBe('dennis@example.com');
});

it('fills the registration email when registering through the register form', function () {
    $this->post(route('register'), [
        'name' => 'Dennis',
        'email' => 'dennis@example.com',
        'password' => 'my-secret-password',
        'password_confirmation' => 'my-secret-password',
        'terms' => true,
    ]);

    expect(User::firstWhere('email', 'dennis@example.com'))
        ->not->toBeNull()
        ->registration_email->toBe('dennis@example.com');
});

it('stores the ip address when a user logs in', function () {
    $user = User::factory()->create();

    expect($user->ip)->toBeNull();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ], ['REMOTE_ADDR' => '1.2.3.4']);

    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->ip)->toBe('1.2.3.4');
});

it('overwrites the ip address on every login', function () {
    $user = User::factory()->create(['ip' => '1.2.3.4']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ], ['REMOTE_ADDR' => '5.6.7.8']);

    expect($user->fresh()->ip)->toBe('5.6.7.8');
});
