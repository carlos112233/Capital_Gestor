<?php

use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Models\Venta;
use App\Models\Articulo;

test('genera mensaje de recordatorio con detalle de compras y precios correctamente', function () {
    $controller = new DashboardController();

    $user = new User([
        'name' => 'Armando García',
        'telefono' => '5512345678',
    ]);
    // Asignamos atributos simulados de saldo
    $user->setAttribute('saldo', 2000.00);
    $user->setAttribute('saldo_corte_anterior', 0);
    $user->setAttribute('saldo_corte_actual', 2000.00);

    // Simulamos artículos
    $articulo1 = new Articulo(['nombre' => 'Zapatos Nike']);
    $articulo2 = new Articulo(['nombre' => 'Playera Adidas']);

    // Simulamos varias compras (ventas)
    $venta1 = new Venta([
        'cantidad' => 1,
        'precio_venta' => 1200.00,
        'total_venta' => 1200.00,
    ]);
    $venta1->setRelation('articulo', $articulo1);

    $venta2 = new Venta([
        'cantidad' => 2,
        'precio_venta' => 400.00,
        'total_venta' => 800.00,
    ]);
    $venta2->setRelation('articulo', $articulo2);

    // Cargamos la relación ventas simulada en el usuario
    $user->setRelation('ventas', collect([$venta1, $venta2]));

    $mensaje = $controller->generarMensajeRecordatorio($user);

    expect($mensaje)
        ->toContain('Hola excelente tarde,  Armando García')
        ->toContain('2,000.00')
        ->toContain('*Detalle de compras:*')
        ->toContain('- 1x Zapatos Nike - $1,200.00')
        ->toContain('- 2x Playera Adidas ($400.00 c/u) - $800.00')
        ->toContain('*DATOS PARA PAGO:*')
        ->toContain('Cuenta: *158 086 7512*');
});

test('ignora productos de pago saldado al generar el detalle de compras', function () {
    $controller = new DashboardController();

    $user = new User([
        'name' => 'Carlos Cliente',
        'telefono' => '5511223344',
    ]);
    $user->setAttribute('saldo', 500.00);
    $user->setAttribute('saldo_corte_anterior', 0);
    $user->setAttribute('saldo_corte_actual', 500.00);

    $articuloPago = new Articulo(['nombre' => 'Pago saldado']);
    $articuloReal = new Articulo(['nombre' => 'Pantalón Levi\'s']);

    $ventaPago = new Venta([
        'cantidad' => 1,
        'precio_venta' => 500.00,
        'total_venta' => 500.00,
    ]);
    $ventaPago->setRelation('articulo', $articuloPago);

    $ventaReal = new Venta([
        'cantidad' => 1,
        'precio_venta' => 1000.00,
        'total_venta' => 1000.00,
    ]);
    $ventaReal->setRelation('articulo', $articuloReal);

    $user->setRelation('ventas', collect([$ventaPago, $ventaReal]));

    $mensaje = $controller->generarMensajeRecordatorio($user);

    expect($mensaje)
        ->toContain('- 1x Pantalón Levi\'s - $1,000.00')
        ->not->toContain('Pago saldado');
});
