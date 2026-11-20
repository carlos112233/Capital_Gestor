<?php

declare(strict_types=1);

use App\Models\Feedback;
use App\Models\FeedbackMensaje;
use App\Models\Role;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Storage::fake('public');
});

function assignFeedbackRole(User $user, string $roleName): void
{
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('cliente autenticado puede crear un feedback, se encola WhatsApp al admin y se registra en estado enviado', function () {
    Notification::fake();

    $admin = User::factory()->create(['telefono' => '2221110099']);
    assignFeedbackRole($admin, 'admin');

    $cliente = User::factory()->create(['name' => 'Elena Feedback']);
    assignFeedbackRole($cliente, 'cliente');

    $adjunto = UploadedFile::fake()->image('evidencia_queja.jpg', 500, 500);

    $response = $this->actingAs($cliente)->post(route('feedback.store'), [
        'tipo'    => 'queja',
        'asunto'  => 'Demora en pedido',
        'mensaje' => 'Mi pedido tardó más de lo esperado hoy.',
        'imagen'  => $adjunto,
    ]);

    $response->assertRedirect(route('feedback.index'));
    $response->assertSessionHas('success');

    $feedback = Feedback::where('user_id', $cliente->id)->first();
    expect($feedback)->not->toBeNull()
        ->and($feedback->tipo)->toBe('queja')
        ->and($feedback->estatus)->toBe('enviado')
        ->and($feedback->asunto)->toBe('Demora en pedido')
        ->and($feedback->imagen)->not->toBeNull();

    Storage::disk('public')->assertExists($feedback->imagen);

    $queuedWa = DB::table(WhatsAppNotificationService::TABLE)
        ->where('numero', '5212221110099')
        ->where('mensaje', 'like', '%Demora en pedido%')
        ->exists();

    expect($queuedWa)->toBeTrue();
});

test('administrador puede abrir el hilo (cambia a leyendo), responder con FeedbackMensaje y actualizar estatus a leido', function () {
    Notification::fake();

    $admin = User::factory()->create(['name' => 'Admin Soporte']);
    assignFeedbackRole($admin, 'admin');

    $cliente = User::factory()->create(['name' => 'Pedro Ticket']);
    assignFeedbackRole($cliente, 'cliente');

    $feedback = Feedback::create([
        'user_id' => $cliente->id,
        'tipo'    => 'sugerencia',
        'asunto'  => 'Agregar más salsas',
        'mensaje' => 'Sería excelente contar con salsa habanera.',
        'estatus' => 'enviado',
    ]);

    // 1. Al abrir show como admin, el estatus transiciona automáticamente de 'enviado' a 'leyendo'
    $this->actingAs($admin)
        ->get(route('feedback.show', $feedback->id))
        ->assertStatus(200);

    expect($feedback->fresh()->estatus)->toBe('leyendo');

    // 2. Responder en el hilo como admin
    $this->actingAs($admin)
        ->post(route('feedback.reply', $feedback->id), [
            'mensaje' => '¡Gracias por tu sugerencia! Ya la incorporamos al menú.',
            'estatus' => 'leyendo',
        ])
        ->assertRedirect(route('feedback.show', $feedback->id));

    expect(FeedbackMensaje::where('feedback_id', $feedback->id)->count())->toBe(1);

    // 3. Actualizar estatus manualmente a 'leido'
    $this->actingAs($admin)
        ->post(route('feedback.status', $feedback->id), [
            'estatus' => 'leido',
        ])
        ->assertSessionHas('success');

    expect($feedback->fresh()->estatus)->toBe('leido');
});

test('control de acceso en FeedbackController impide que un cliente vea o responda hilos ajenos (403) y redirige invitados', function () {
    Notification::fake();

    $clientePropietario = User::factory()->create();
    assignFeedbackRole($clientePropietario, 'cliente');

    $clienteIntruso = User::factory()->create();
    assignFeedbackRole($clienteIntruso, 'cliente');

    $feedback = Feedback::create([
        'user_id' => $clientePropietario->id,
        'tipo'    => 'comentario',
        'asunto'  => 'Ticket privado',
        'mensaje' => 'Detalle confidencial del cliente.',
        'estatus' => 'enviado',
    ]);

    // Cliente intruso no puede ver, responder ni cambiar estado del ticket
    $this->actingAs($clienteIntruso)
        ->get(route('feedback.show', $feedback->id))
        ->assertStatus(403);

    $this->actingAs($clienteIntruso)
        ->post(route('feedback.reply', $feedback->id), [
            'mensaje' => 'Intento de respuesta no autorizada',
        ])
        ->assertStatus(403);

    $this->actingAs($clienteIntruso)
        ->post(route('feedback.status', $feedback->id), [
            'estatus' => 'leido',
        ])
        ->assertStatus(403);

    // Invitado no autenticado es redirigido al login
    auth()->logout();
    $this->get(route('feedback.index'))
        ->assertRedirect('/login');
});
