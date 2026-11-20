<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login is locked out on 6th failed attempt for same email and IP and dispatches Lockout event', function () {
    Event::fake([Lockout::class]);

    $user = User::factory()->create([
        'email' => 'lockout.target@elbajon.store',
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password-' . $i,
        ])->assertRedirect('/login');
    }

    // 6to intento fallido para la misma combinación email|IP dispara Lockout
    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password-6',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email']);
    Event::assertDispatched(Lockout::class);
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
