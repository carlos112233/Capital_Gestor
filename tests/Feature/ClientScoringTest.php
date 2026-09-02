<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Comprobante;
use App\Models\Pedido;
use App\Models\Articulo;
use App\Services\ClientScoringService;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
});

function assignRoleToUserForScoring(User $user, string $roleName) {
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('calcula correctamente el scoring crediticio automatico de un cliente', function () {
    $cliente = User::factory()->create([
        'score_calculado' => 70,
        'override_score' => false,
    ]);
    assignRoleToUserForScoring($cliente, 'cliente');

    // 1. Base 70 sin movimientos
    $scoring = ClientScoringService::getScoring($cliente);
    expect($scoring['score'])->toBe(70)
        ->and($scoring['tier'])->toBe('regular');

    // 2. Agregar 2 comprobantes aprobados (+20) y 1 rechazado (-15) => 75
    Comprobante::create([
        'user_id' => $cliente->id,
        'monto' => 500,
        'status' => 'aprobado',
        'imagen' => 'comprobantes/test1.jpg',
    ]);
    Comprobante::create([
        'user_id' => $cliente->id,
        'monto' => 300,
        'status' => 'aprobado',
        'imagen' => 'comprobantes/test2.jpg',
    ]);
    Comprobante::create([
        'user_id' => $cliente->id,
        'monto' => 200,
        'status' => 'rechazado',
        'imagen' => 'comprobantes/test3.jpg',
    ]);

    $scoringUpdated = ClientScoringService::getScoring($cliente->fresh());
    expect($scoringUpdated['score'])->toBe(75)
        ->and($scoringUpdated['tier'])->toBe('regular');
});

test('administrador puede sobreescribir el score crediticio de forma manual', function () {
    $admin = User::factory()->create();
    assignRoleToUserForScoring($admin, 'admin');

    $cliente = User::factory()->create([
        'score_calculado' => 60,
        'override_score' => false,
    ]);
    assignRoleToUserForScoring($cliente, 'cliente');

    // Sobreescribir manualmente a 95 (Platino VIP)
    $response = $this->actingAs($admin)
        ->postJson(route('admin.scoring.update', $cliente->id), [
            'override_score' => true,
            'score_manual' => 95,
            'notas_scoring' => 'Cliente VIP preferencial por historial externo',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'scoring' => [
                'score' => 95,
                'tier' => 'platino',
                'is_override' => true,
            ]
        ]);

    $cliente->refresh();
    expect((bool) $cliente->override_score)->toBeTrue()
        ->and((int) $cliente->score_manual)->toBe(95)
        ->and($cliente->notas_scoring)->toBe('Cliente VIP preferencial por historial externo');
});

test('endpoint de analitica devuelve la estructura esperada para apexcharts', function () {
    $admin = User::factory()->create();
    assignRoleToUserForScoring($admin, 'admin');

    $response = $this->actingAs($admin)
        ->getJson(route('admin.scoring.analytics'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'kpi' => [
                'total_cobrado',
                'saldo_pendiente',
                'count_vip',
                'count_regular',
                'count_riesgo',
            ],
            'donut' => [
                'aprobado',
                'procesando_pago',
                'rechazado',
                'pendiente',
            ],
            'trend' => [
                'categories',
                'ventas',
                'entradas',
            ],
        ]);
});
