<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Models\Articulo;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegistrarVentaAction
{
    /**
     * Registra una venta individual con bloqueo pesimista, autoridad de precio maestro y notificación en DB::afterCommit().
     *
     * @param array{articulo_id: int|string, cantidad: int|string, cliente_id?: int|string|null, user_id?: int|string|null, descripcion?: string|null} $data
     */
    public function executeSingle(array $data, User $operador, ?int $pedidoId = null): Venta
    {
        return DB::transaction(function () use ($data, $operador, $pedidoId): Venta {
            $articulo = Articulo::lockForUpdate()->findOrFail((int) $data['articulo_id']);
            $cantidadVenta = (int) $data['cantidad'];

            if ($articulo->stock < $cantidadVenta) {
                throw ValidationException::withMessages([
                    'cantidad' => "Stock insuficiente. Disponible: {$articulo->stock}",
                ]);
            }

            $isAdmin = $operador->hasRole('admin');
            $isOperadorVenta = $isAdmin || $operador->hasRole('vendedor');
            $targetClientId = $data['cliente_id'] ?? $data['user_id'] ?? null;

            $finalUserId = ($isOperadorVenta && !empty($targetClientId))
                ? (int) $targetClientId
                : (int) $operador->id;

            $articulo->decrement('stock', $cantidadVenta);
            if ($articulo->stock <= 0) {
                $articulo->disponible = false;
                $articulo->save();
            }

            // Autoridad estricta del precio maestro de catálogo
            $precioUnitario = (float) $articulo->precio;
            $totalVenta = $precioUnitario * $cantidadVenta;

            /** @var Venta $venta */
            $venta = $articulo->ventas()->create([
                'user_id'      => $finalUserId,
                'comprador_id' => (int) $operador->id,
                'cantidad'     => $cantidadVenta,
                'precio_venta' => $precioUnitario,
                'total_venta'  => $totalVenta,
                'descripcion'  => $data['descripcion'] ?? null,
            ]);

            if (!empty($pedidoId)) {
                $pedido = Pedido::find($pedidoId);
                if ($pedido) {
                    $pedido->update(['venta_id' => $venta->id]);
                }
            }

            if (!$isAdmin) {
                DB::afterCommit(function () use ($venta): void {
                    try {
                        Venta::notificarAdminWhatsApp($venta);
                    } catch (\Throwable $eNotification) {
                        Log::error("Fallo no bloqueante al notificar venta #{$venta->id} por WhatsApp: " . $eNotification->getMessage(), [
                            'exception' => $eNotification,
                            'venta_id'  => $venta->id,
                        ]);
                    }
                });
            }

            return $venta;
        });
    }

    /**
     * Registra un lote/carrito de múltiples ventas en una sola transacción ACID con bloqueo pesimista y notificación post-commit.
     *
     * @param array<int, array{articulo_id: int|string, cantidad: int|string, user_id?: int|string|null, descripcion?: string|null}> $ventasData
     * @return array<int, Venta>
     */
    public function executeMultiple(array $ventasData, User $operador): array
    {
        return DB::transaction(function () use ($ventasData, $operador): array {
            $isAdmin = $operador->hasRole('admin');
            $isOperadorVenta = $isAdmin || $operador->hasRole('vendedor');
            $authUserId = (int) $operador->id;

            $articuloIds = collect($ventasData)
                ->pluck('articulo_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $articulosBloqueados = Articulo::whereIn('id', $articuloIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validar stock acumulado por artículo en todo el carrito
            $cantidadesRequeridas = [];
            foreach ($ventasData as $item) {
                $artId = (int) $item['articulo_id'];
                $cantidadesRequeridas[$artId] = ($cantidadesRequeridas[$artId] ?? 0) + (int) $item['cantidad'];
            }

            foreach ($cantidadesRequeridas as $artId => $cantidadTotal) {
                /** @var Articulo|null $articulo */
                $articulo = $articulosBloqueados->get($artId);
                if (!$articulo || $articulo->stock < $cantidadTotal) {
                    $nombre = $articulo->nombre ?? "#{$artId}";
                    $disp = $articulo->stock ?? 0;
                    throw ValidationException::withMessages([
                        'ventas' => "Stock insuficiente para el artículo '{$nombre}'. Disponible: {$disp}",
                    ]);
                }
            }

            $ventasCreadasParaResumen = [];
            $totalCarrito = 0.0;

            foreach ($ventasData as $ventaData) {
                $artId = (int) $ventaData['articulo_id'];
                /** @var Articulo $articulo */
                $articulo = $articulosBloqueados->get($artId);
                $cantidadVenta = (int) $ventaData['cantidad'];

                $finalUserId = ($isOperadorVenta && !empty($ventaData['user_id']))
                    ? (int) $ventaData['user_id']
                    : $authUserId;

                $articulo->decrement('stock', $cantidadVenta);
                if ($articulo->stock <= 0) {
                    $articulo->disponible = false;
                    $articulo->save();
                }

                $precioUnitario = (float) $articulo->precio;
                $totalVenta = $precioUnitario * $cantidadVenta;
                $totalCarrito += $totalVenta;

                /** @var Venta $venta */
                $venta = $articulo->ventas()->create([
                    'user_id'      => $finalUserId,
                    'comprador_id' => $authUserId,
                    'cantidad'     => $cantidadVenta,
                    'precio_venta' => $precioUnitario,
                    'total_venta'  => $totalVenta,
                    'descripcion'  => $ventaData['descripcion'] ?? null,
                ]);

                $ventasCreadasParaResumen[] = $venta;
            }

            if (!$isAdmin && count($ventasCreadasParaResumen) > 0) {
                DB::afterCommit(function () use ($ventasCreadasParaResumen, $totalCarrito): void {
                    try {
                        Venta::notificarCarritoAdminWhatsApp($ventasCreadasParaResumen, $totalCarrito);
                    } catch (\Throwable $eNotification) {
                        Log::error("Fallo no bloqueante al notificar carrito por WhatsApp: " . $eNotification->getMessage(), [
                            'exception' => $eNotification,
                            'total'     => $totalCarrito,
                        ]);
                    }
                });
            }

            return $ventasCreadasParaResumen;
        });
    }
}
