<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Models\AppSetting;
use App\Models\Articulo;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\AppNotification;
use App\Services\PushNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrarPedidoAction
{
    public function __construct(
        private readonly WhatsAppNotificationService $whatsAppService
    ) {}

    /**
     * Registra uno o múltiples pedidos junto con sus ventas asociadas en una transacción ACID con lockForUpdate,
     * clasificación estructurada de cocina por columnas (requiere_cocina, categoria_cocina) y despacho DB::afterCommit().
     *
     * @param array<int, array{articulo_id: int|string, cantidad: int|string, descripcion?: string|null, user_id?: int|string|null}> $pedidosData
     * @return array<int, Pedido>
     */
    public function execute(array $pedidosData, User $actor, ?int $defaultUserId = null): array
    {
        $isAdmin = $actor->hasRole('admin');
        $actorId = (int) $actor->id;
        $baseUserId = $isAdmin ? ($defaultUserId ?? $actorId) : $actorId;

        $adminPhones = $this->whatsAppService->getAdminPhones();
        $cocinaPhones = $this->whatsAppService->getCocinaPhones();

        $articuloIds = collect($pedidosData)
            ->pluck('articulo_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return DB::transaction(function () use (
            $pedidosData,
            $articuloIds,
            $baseUserId,
            $actorId,
            $isAdmin,
            $adminPhones,
            $cocinaPhones
        ): array {
            $articulosBloqueados = Articulo::whereIn('id', $articuloIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validar disponibilidad y stock acumulado por artículo antes de mutar inventario
            $cantidadesRequeridas = [];
            foreach ($pedidosData as $item) {
                $artId = (int) $item['articulo_id'];
                $cantidadesRequeridas[$artId] = ($cantidadesRequeridas[$artId] ?? 0) + (int) $item['cantidad'];
            }

            foreach ($cantidadesRequeridas as $artId => $cantidadTotal) {
                /** @var Articulo|null $articulo */
                $articulo = $articulosBloqueados->get($artId);
                if (!$articulo || !$articulo->disponible) {
                    $nombreArt = $articulo->nombre ?? "#{$artId}";
                    throw ValidationException::withMessages([
                        'pedidos' => "El artículo '{$nombreArt}' no se encuentra disponible.",
                    ]);
                }

                if ((int) $articulo->stock < $cantidadTotal) {
                    throw ValidationException::withMessages([
                        'pedidos' => "Stock insuficiente para el artículo '{$articulo->nombre}'. Disponible: {$articulo->stock}",
                    ]);
                }
            }

            $cemitasPollo = 0;
            $cemitasPuerco = 0;
            $tieneCemitas = false;
            $clienteNombreResumen = 'Cliente';
            $itemsParaCocina = [];
            $itemsParaAdmin = [];
            $pedidosCreados = [];
            $mensajesIndividualesAdmin = [];
            $notificacionesCocinaWebPush = [];
            $totalPedidoGlobal = 0.0;

            foreach ($pedidosData as $p) {
                $artId = (int) $p['articulo_id'];
                /** @var Articulo $articulo */
                $articulo = $articulosBloqueados->get($artId);
                $cantidadPedido = (int) $p['cantidad'];

                $articulo->decrement('stock', $cantidadPedido);
                if ($articulo->stock <= 0) {
                    $articulo->disponible = false;
                    $articulo->save();
                }

                $precioUnitario = (float) $articulo->precio;
                $total = $precioUnitario * $cantidadPedido;
                $totalPedidoGlobal += $total;
                $targetUserId = !empty($p['user_id']) ? (int) $p['user_id'] : (int) $baseUserId;

                $venta = Venta::create([
                    'user_id'      => $targetUserId,
                    'comprador_id' => $actorId,
                    'articulo_id'  => $artId,
                    'cantidad'     => $cantidadPedido,
                    'precio_venta' => $precioUnitario,
                    'total_venta'  => $total,
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);

                $pedido = Pedido::create([
                    'user_id'     => $targetUserId,
                    'articulo_id' => $artId,
                    'descripcion' => $p['descripcion'] ?? '',
                    'costo'       => $total,
                    'venta_id'    => $venta->id,
                    'cantidad'    => $cantidadPedido,
                ]);

                $pedido->load('articulo', 'user');
                $pedidosCreados[] = $pedido;

                $articuloNombre = $articulo->nombre ?? 'N/A';
                $clienteNombre = $pedido->user->name ?? 'Cliente';
                $clienteNombreResumen = $clienteNombre;

                // Clasificación de cocina 100% basada en metadatos estructurados de Articulo (cero str_contains)
                $esCemita = $articulo->esCemitaOMilanesa();

                if ($esCemita) {
                    $tieneCemitas = true;
                    $conteo = $articulo->conteoMilanesas($cantidadPedido);
                    $cemitasPollo += $conteo['pollo'];
                    $cemitasPuerco += $conteo['puerco'];
                }

                $totalFormatted = number_format((float) ($pedido->costo ?? 0), 2);
                $notasStr = $pedido->descripcion ?: 'Sin notas';
                $lineaItem = "• *{$articuloNombre}* (x{$pedido->cantidad}) = \${$totalFormatted}\n  _Nota:_ {$notasStr}";

                if ($articulo->requierePreparacionPlatilloCocina()) {
                    $itemsParaCocina[] = "• *{$articuloNombre}* (x{$pedido->cantidad})\n  _Nota:_ {$notasStr}";
                    $notificacionesCocinaWebPush[] = [
                        'pedido_id'       => $pedido->id,
                        'articulo_nombre' => $articuloNombre,
                        'cliente_nombre'  => $clienteNombre,
                    ];
                }

                $itemsParaAdmin[] = $lineaItem;

                if ($isAdmin && $esCemita) {
                    $appName = AppSetting::getSettings()->app_name ?? 'El Bajón';
                    $mensajesIndividualesAdmin[] = "*📦 NUEVO PEDIDO DE CEMITA #{$pedido->id} - {$appName}*\n\n" .
                        "• *Cliente:* {$clienteNombre}\n" .
                        "• *Artículo:* {$articuloNombre}\n" .
                        "• *Cantidad:* {$pedido->cantidad}\n" .
                        "• *Total:* \${$totalFormatted}\n" .
                        "• *Notas:* {$notasStr}\n\n" .
                        "_Enviado por {$appName}_";
                }
            }

            DB::afterCommit(function () use (
                $isAdmin,
                $adminPhones,
                $cocinaPhones,
                $mensajesIndividualesAdmin,
                $notificacionesCocinaWebPush,
                $itemsParaAdmin,
                $itemsParaCocina,
                $tieneCemitas,
                $cemitasPollo,
                $cemitasPuerco,
                $clienteNombreResumen,
                $totalPedidoGlobal,
                $pedidosCreados
            ): void {
                // Notificaciones individuales de cemitas cuando pide Admin
                foreach ($mensajesIndividualesAdmin as $mensajeWaIndiv) {
                    try {
                        $this->whatsAppService->enqueueToMultiple($adminPhones, $mensajeWaIndiv);
                    } catch (\Throwable $exWa) {
                        Log::error("Error encolando WhatsApp individual de cemita: " . $exWa->getMessage());
                    }
                }

                // Notificaciones WebPush a Cocina por cada platillo que requiere cocina
                if (!empty($notificacionesCocinaWebPush)) {
                    try {
                        $cocinaUsers = User::whereHas('roles', function ($q) {
                            $q->where('name', 'cocina');
                        })->get();

                        foreach ($notificacionesCocinaWebPush as $notifCocina) {
                            Notification::send($cocinaUsers, new AppNotification(
                                "PREPARAR PEDIDO #{$notifCocina['pedido_id']} 🧑‍🍳",
                                "{$notifCocina['articulo_nombre']} para {$notifCocina['cliente_nombre']}",
                                route('pedidos.index')
                            ));
                        }
                    } catch (\Throwable $exCocinaPush) {
                        Log::error("Error enviando WebPush a cocina: " . $exCocinaPush->getMessage());
                    }
                }

                // 1. AL ADMIN: Si es un usuario normal, resumen consolidado por WhatsApp
                if (!$isAdmin && count($itemsParaAdmin) > 0 && !empty($adminPhones)) {
                    try {
                        $appName = AppSetting::getSettings()->app_name ?? 'El Bajón';
                        $totalMonto = number_format($totalPedidoGlobal, 2);
                        $totalPiezasPedidos = array_sum(array_map(fn ($p) => (int) ($p->cantidad ?? 1), $pedidosCreados));
                        $mensajeResumenAdmin = "*📦 NUEVO PEDIDO MÚLTIPLE - {$appName}*\n\n" .
                            "• *Cliente:* {$clienteNombreResumen}\n" .
                            "• *Total de productos:* {$totalPiezasPedidos} unidad(es)\n\n" .
                            "*PRODUCTOS:*\n" .
                            implode("\n", $itemsParaAdmin) . "\n\n" .
                            "*TOTAL DEL PEDIDO:* \${$totalMonto}\n\n" .
                            "_Enviado por {$appName}_";

                        $this->whatsAppService->enqueueToMultiple($adminPhones, $mensajeResumenAdmin);
                    } catch (\Throwable $exAdminWa) {
                        Log::error("Error enviando resumen WhatsApp a admin: " . $exAdminWa->getMessage());
                    }
                }

                // 2. A COCINA: Resumen de platillos y/o milanesas
                if ((!empty($itemsParaCocina) || $tieneCemitas) && !empty($cocinaPhones)) {
                    try {
                        $appName = AppSetting::getSettings()->app_name ?? 'El Bajón';
                        $mensajeResumenCocina = "*🧑‍🍳 NUEVA ORDEN COMPLETA - {$appName}*\n\n" .
                            "• *Cliente:* {$clienteNombreResumen}\n\n";

                        if (!empty($itemsParaCocina)) {
                            $mensajeResumenCocina .= "*PLATILLOS:*\n" . implode("\n------------------------------------------\n", $itemsParaCocina) . "\n\n";
                        }

                        if ($tieneCemitas) {
                            $mensajeResumenCocina .= "------------------------------------------\n" .
                                "*RESUMEN DE MILANESAS:*\n" .
                                "• Pollo a preparar: {$cemitasPollo}\n" .
                                "• Puerco a preparar: {$cemitasPuerco}\n\n" .
                                "_(Las milanesas necesarias para todo este pedido)_\n\n";
                        }

                        $mensajeResumenCocina .= "_Enviado por {$appName}_";

                        $this->whatsAppService->enqueueToMultiple($cocinaPhones, $mensajeResumenCocina);
                    } catch (\Throwable $ex) {
                        Log::error("Error enviando resumen completo a cocina: " . $ex->getMessage());
                    }
                }

                // 3. Notificación Push al Administrador
                if (!$isAdmin) {
                    try {
                        $totalPiezasPedidos = array_sum(array_map(fn ($p) => (int) ($p->cantidad ?? 1), $pedidosCreados));
                        $itemsNombres = array_map(function ($p) {
                            $artNom = $p->articulo->nombre ?? 'Artículo';
                            $cant = (int) ($p->cantidad ?? 1);
                            return "{$cant}x {$artNom}";
                        }, $pedidosCreados);
                        $listaPedidos = Str::limit(implode(', ', $itemsNombres), 180, '...');
                        $totalMonto = number_format($totalPedidoGlobal, 2);

                        PushNotificationService::notifyAdmins(
                            "Nuevo Pedido Recibido 📦",
                            "El cliente {$clienteNombreResumen} realizó un pedido de {$totalPiezasPedidos} producto(s) por un total de \${$totalMonto}: {$listaPedidos}.",
                            route('pedidos.index')
                        );
                    } catch (\Throwable $exPushAdmin) {
                        Log::error("Error enviando Push de nuevo pedido a admins: " . $exPushAdmin->getMessage());
                    }
                }
            });

            return $pedidosCreados;
        });
    }
}
