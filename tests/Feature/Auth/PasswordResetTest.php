<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('solicitar reset por WhatsApp no modifica users.password y encola enlace con token sin contrasena en texto plano', function () {
    $originalHash = Hash::make('MiClaveOriginalSegura123!');
    $user = User::factory()->create([
        'email' => 'cliente.reset@elbajon.store',
        'telefono' => '2221234567',
        'password' => $originalHash,
    ]);

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');

    // (a) Verificar que users.password NO fue modificado al solicitar el reset
    $user->refresh();
    expect($user->password)->toBe($originalHash)
        ->and(Hash::check('MiClaveOriginalSegura123!', $user->password))->toBeTrue();

    // (b) Verificar que el mensaje en whatsapp_pending_messages contiene el link con token y NO una contraseña temporal en texto plano
    $queuedMsg = DB::table('whatsapp_pending_messages')
        ->where('numero', '5212221234567')
        ->latest('id')
        ->first();

    expect($queuedMsg)->not->toBeNull()
        ->and($queuedMsg->mensaje)->toContain('/reset-password/')
        ->and($queuedMsg->mensaje)->toContain(urlencode($user->email))
        ->and($queuedMsg->mensaje)->not->toContain('Bajon-')
        ->and($queuedMsg->mensaje)->not->toContain('Nueva Contraseña Temporal');
});

test('reset password screen can be rendered', function () {
    $user = User::factory()->create([
        'telefono' => '2229876543',
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->get(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]));

    $response->assertStatus(200);
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create([
        'telefono' => '2229876543',
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NuevaClaveSegura99!',
        'password_confirmation' => 'NuevaClaveSegura99!',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('NuevaClaveSegura99!', $user->fresh()->password))->toBeTrue();
});

test('exceder throttle 3,15 en forgot-password retorna HTTP 429', function () {
    $user = User::factory()->create([
        'email' => 'throttle.user@elbajon.store',
        'telefono' => '2225554433',
    ]);

    for ($i = 1; $i <= 3; $i++) {
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('login'));
    }

    // El 4to intento dentro de la ventana de 15 minutos debe ser bloqueado con HTTP 429
    $this->post('/forgot-password', ['email' => $user->email])
        ->assertStatus(429);
});
