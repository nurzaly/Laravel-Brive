<?php

use App\Models\User;

it('does not offer a public registration page', function () {
    $this->get('/register')->assertNotFound();
});

it('does not accept public registrations', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::whereEmail('test@example.com')->exists())->toBeFalse();
});

it('hides the register link on the welcome page', function () {
    $this->get('/')->assertInertia(fn ($page) => $page->where('canRegister', false));
});
