<?php

declare(strict_types=1);

use App\Actions\Pos\RegistrarPedidoAction;
use App\Actions\Pos\RegistrarVentaAction;
use App\Models\Articulo;
use App\Models\Role;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Role::firstOrCreate(['name' => 'vendedor']);
    Role::firstOrCreate(['name' => 'cocina']);
});

function assignPosRole(User $user, string $roleName): void
{
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('RegistrarVentaAction ejecuta venta individual y multiple forzando precio maestro y descontando stock', function () {
    Notification::fake();

    $cliente = User::factory()->create(['name' => 'Cliente POS Action']);
    assignPosRole($cliente, 'cliente');

    $art1 = Articulo::create([
        'nombre'           => 'Especialidad Alfa',
        'precio'           => 115.00,
        'stock'            => 10,
        'disponible'       => true,
        'requiere_cocina'  => true,
        'categoria_cocina' => Articulo::CATEGORIA_OTRO,
    ]);

    $art2 = Articulo::create([
        'nombre'           => 'Bebida Beta',
        'precio'           => 30.00,
        'stock'            => 4,
        'disponible'       => true,
        'requiere_cocina'  => false,
        'categoria_cocina' => Articulo::CATEGORIA_BEBIDA,
    ]);

    $action = app(RegistrarVentaAction::class);

    // 1. Venta individual
    $ventaSingle = $action->executeSingle([
        'articulo_id' => $art1->id,
        'cantidad'    => 2,
        'descripcion' => 'Venta individual action',
    ], $cliente);

    expect((float) $ventaSingle->precio_venta)->toBe(115.00)
        ->and((float) $ventaSingle->total_venta)->toBe(230.00)
        ->and($art1->fresh()->stock)->toBe(8);

    // 2. Venta múltiple (carrito) agotando stock de art2
    $ventasLote = $action->executeMultiple([
        [
            'articulo_id' => $art1->id,
            'cantidad'    => 3,
            'descripcion' => 'Lote item 1',
        ],
        [
            'articulo_id' => $art2->id,
            'cantidad'    => 4,
            'descripcion' => 'Lote item 2',
        ],
    ], $cliente);

    expect($ventasLote)->toHaveCount(2)
        ->and($art1->fresh()->stock)->toBe(5)
        ->and($art2->fresh()->stock)->toBe(0)
        ->and($art2->fresh()->disponible)->toBeFalse();
});

test('RegistrarPedidoAction clasifica cocina y milanesas por categoria_cocina sin depender del nombre del producto', function () {
    Notification::fake();

    $cocinero = User::factory()->create([
        'name'     => 'Chef Cocina',
        'telefono' => '2223334455',
    ]);
    assignPosRole($cocinero, 'cocina');

    $cliente = User::factory()->create([
        'name'     => 'Lucia Pedidos',
        'telefono' => '2227778899',
    ]);
    assignPosRole($cliente, 'cliente');

    // Nombres intencionalmente neutros (sin las palabras "cemita", "pollo" o "puerco")
    // para demostrar que la clasificación depende 100% de categoria_cocina y requiere_cocina
    $itemCombinado = Articulo::create([
        'nombre'           => 'Combo Imperial X1',
        'precio'           => 130.00,
        'stock'            => 10,
        'disponible'       => true,
        'requiere_cocina'  => true,
        'categoria_cocina' => Articulo::CATEGORIA_CEMITA, // 1 pollo + 1 puerco por unidad
    ]);

    $itemSoloPollo = Articulo::create([
        'nombre'           => 'Especialidad Dorada B2',
        'precio'           => 95.00,
        'stock'            => 10,
        'disponible'       => true,
        'requiere_cocina'  => true,
        'categoria_cocina' => Articulo::CATEGORIA_MILANESA_POLLO, // 1 pollo por unidad
    ]);

    $itemBebida = Articulo::create([
        'nombre'           => 'Hidratante Cristal C3',
        'precio'           => 25.00,
        'stock'            => 10,
        'disponible'       => true,
        'requiere_cocina'  => false,
        'categoria_cocina' => Articulo::CATEGORIA_BEBIDA, // No va a cocina
    ]);

    $pedidos = app(RegistrarPedidoAction::class)->execute([
        [
            'articulo_id' => $itemCombinado->id,
            'cantidad'    => 2, // 2 pollo + 2 puerco
            'descripcion' => 'Sin picante',
        ],
        [
            'articulo_id' => $itemSoloPollo->id,
            'cantidad'    => 3, // 3 pollo
            'descripcion' => 'Extra crujiente',
        ],
        [
            'articulo_id' => $itemBebida->id,
            'cantidad'    => 1, // 0 cocina
            'descripcion' => 'Fría',
        ],
    ], $cliente);

    expect($pedidos)->toHaveCount(3)
        ->and($itemCombinado->fresh()->stock)->toBe(8)
        ->and($itemSoloPollo->fresh()->stock)->toBe(7)
        ->and($itemBebida->fresh()->stock)->toBe(9);

    // Verificar mensaje encolado a cocina con 5 de pollo (2 + 3) y 2 de puerco
    $msgCocina = DB::table(WhatsAppNotificationService::TABLE)
        ->where('numero', '5212223334455')
        ->latest('id')
        ->first();

    expect($msgCocina)->not->toBeNull()
        ->and($msgCocina->mensaje)->toContain('Pollo a preparar: 5')
        ->and($msgCocina->mensaje)->toContain('Puerco a preparar: 2')
        ->and($msgCocina->mensaje)->not->toContain('Hidratante Cristal C3');
});

test('WhatsAppNotificationService centraliza el telefono de respaldo desde config services y gestiona estados', function () {
    // Asegurar que no existan admins con teléfono en este escenario
    DB::table('users')->update(['telefono' => null]);
    Config::set('services.whatsapp.admin_fallback_phone', '5219998887766');

    $waService = app(WhatsAppNotificationService::class);
    $adminPhones = $waService->getAdminPhones();

    expect($adminPhones)->toBe(['5219998887766']);

    $count = $waService->enqueueToAdmins('Mensaje centralizado de prueba');
    expect($count)->toBe(1);

    $msg = DB::table(WhatsAppNotificationService::TABLE)
        ->where('numero', '5219998887766')
        ->latest('id')
        ->first();

    expect($msg)->not->toBeNull()
        ->and($msg->status)->toBe('pendiente');

    $waService->markAsSent((int) $msg->id);
    expect(DB::table(WhatsAppNotificationService::TABLE)->where('id', $msg->id)->value('status'))->toBe('enviado');

    $waService->markAsPending((int) $msg->id);
    expect(DB::table(WhatsAppNotificationService::TABLE)->where('id', $msg->id)->value('status'))->toBe('pendiente');
});
