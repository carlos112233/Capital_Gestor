<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PagoVendedor;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PagoVendedorController extends Controller
{
    /**
     * Obtiene la configuración de porcentajes de retención (global y por vendedor).
     *
     * @return array{default: float, vendedores: array<string, float>}
     */
    public static function getRetencionConfig(): array
    {
        $path = storage_path('app/retencion_vendedores.json');
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                return [
                    'default'    => isset($decoded['default']) ? (float) $decoded['default'] : 20.00,
                    'vendedores' => isset($decoded['vendedores']) && is_array($decoded['vendedores']) ? $decoded['vendedores'] : [],
                ];
            }
        }

        return [
            'default'    => 20.00,
            'vendedores' => [],
        ];
    }

    /**
     * Devuelve el porcentaje de retención aplicable a un vendedor (por defecto 20%).
     */
    public static function getPorcentajeRetencion(?int $vendedorId = null): float
    {
        $config = self::getRetencionConfig();
        if ($vendedorId !== null && isset($config['vendedores'][(string) $vendedorId])) {
            return (float) $config['vendedores'][(string) $vendedorId];
        }

        return (float) ($config['default'] ?? 20.00);
    }

    /**
     * Vista dedicada de Nómina y Liquidaciones de Vendedores (Solo Admin).
     */
    public function index(Request $request): View
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso exclusivo para administradores.');
        }

        $retencionDefault = self::getPorcentajeRetencion(null);

        // 1. Resumen de Vendedores Activos (Pre-carga con agregados SQL para 0 consultas N+1)
        $vendedores = User::with('roles')
            ->whereHas('roles', fn($q) => $q->where('name', 'vendedor'))
            ->select('id', 'name', 'email', 'telefono', 'image', DB::raw('image IS NOT NULL as has_image'))
            ->withSum('comprasRealizadas as ventas_operador_sum', 'total_venta')
            ->withCount('comprasRealizadas as ventas_operador_count')
            ->withSum('pagosRecibidos as pagos_recibidos_sum', 'monto_neto_pagado')
            ->withSum('pagosRecibidos as base_liquidada_sum', 'total_vendido')
            ->orderBy('name')
            ->get()
            ->map(function (User $v) {
                $totalVendido = (float) ($v->ventas_operador_sum ?? 0);
                $totalPagadoNeto = (float) ($v->pagos_recibidos_sum ?? 0);
                $totalBaseLiquidada = (float) ($v->base_liquidada_sum ?? 0);

                $porcentajeRetencion = self::getPorcentajeRetencion((int) $v->id);
                $porcentajeGanancia = round(100.0 - $porcentajeRetencion, 2);
                $montoRetenido = round($totalVendido * ($porcentajeRetencion / 100), 2);
                $gananciaNeta = round($totalVendido - $montoRetenido, 2);

                $basePendiente = max(0.0, round($totalVendido - $totalBaseLiquidada, 2));
                $saldoPendienteCobro = max(0.0, round($gananciaNeta - $totalPagadoNeto, 2));

                $v->porcentaje_retencion = $porcentajeRetencion;
                $v->porcentaje_ganancia = $porcentajeGanancia;
                $v->total_vendido = $totalVendido;
                $v->monto_retenido = $montoRetenido;
                $v->ganancia_neta = $gananciaNeta;
                $v->comision_estimada = $gananciaNeta;
                $v->total_pagado_neto = $totalPagadoNeto;
                $v->total_base_liquidada = $totalBaseLiquidada;
                $v->base_pendiente = $basePendiente;
                $v->saldo_pendiente = $saldoPendienteCobro;

                return $v;
            });

        // KPIs Globales del Módulo
        $kpis = [
            'total_vendido'   => (float) $vendedores->sum('total_vendido'),
            'monto_retenido'  => (float) $vendedores->sum('monto_retenido'),
            'ganancia_neta'   => (float) $vendedores->sum('ganancia_neta'),
            'total_pagado'    => (float) $vendedores->sum('total_pagado_neto'),
            'saldo_pendiente' => (float) $vendedores->sum('saldo_pendiente'),
        ];

        // 2. Historial Completo de Pagos Realizados (Auditable con Eager Loading)
        $historialPagos = PagoVendedor::with([
                'vendedor:id,name,email,telefono,image',
                'registradoPor:id,name',
            ])
            ->when($request->filled('vendedor_id'), function ($q) use ($request) {
                $q->where('vendedor_id', (int) $request->input('vendedor_id'));
            })
            ->when($request->filled('metodo_pago'), function ($q) use ($request) {
                $q->where('metodo_pago', (string) $request->input('metodo_pago'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.pagos_vendedores.index', compact(
            'vendedores',
            'kpis',
            'historialPagos',
            'retencionDefault'
        ));
    }

    /**
     * Actualiza el porcentaje de retención global o individual de un vendedor.
     */
    public function updateRetencion(Request $request): RedirectResponse
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        $validated = $request->validate([
            'vendedor_id'          => 'nullable|integer|exists:users,id',
            'porcentaje_retencion' => 'required|numeric|min:0|max:100',
            'aplicar_todos'        => 'nullable|boolean',
        ]);

        $config = self::getRetencionConfig();
        $nuevoPorcentaje = round((float) $validated['porcentaje_retencion'], 2);

        if (!empty($validated['vendedor_id']) && !$request->boolean('aplicar_todos')) {
            $vendedorId = (string) $validated['vendedor_id'];
            $config['vendedores'][$vendedorId] = $nuevoPorcentaje;
            $vendedor = User::find((int) $validated['vendedor_id']);
            $mensaje = "Porcentaje de retención para {$vendedor?->name} actualizado al {$nuevoPorcentaje}% (Ganancia neta: " . (100 - $nuevoPorcentaje) . "%).";
        } else {
            $config['default'] = $nuevoPorcentaje;
            if ($request->boolean('aplicar_todos')) {
                $config['vendedores'] = [];
            }
            $mensaje = "Porcentaje de retención global actualizado al {$nuevoPorcentaje}% (Ganancia neta vendedor: " . (100 - $nuevoPorcentaje) . "%).";
        }

        file_put_contents(
            storage_path('app/retencion_vendedores.json'),
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return redirect()->route('admin.pagos-vendedores.index')
            ->with('success', $mensaje);
    }

    /**
     * Registra una liquidación de nómina/comisiones a un vendedor.
     */
    public function store(Request $request): RedirectResponse
    {
        // Solo un administrador puede autorizar y liquidar
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada. Solo los administradores pueden autorizar y registrar liquidaciones.');
        }

        $validated = $request->validate([
            'vendedor_id'          => 'required|exists:users,id',
            'total_vendido'        => 'required|numeric|min:0.01',
            'porcentaje_retencion' => 'required|numeric|min:0|max:100',
            'monto_retenido'       => 'required|numeric|min:0',
            'monto_neto_pagado'    => 'required|numeric|min:0.01',
            'metodo_pago'          => 'required|string|max:50',
            'notas'                => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                $totalVendido = (float) $validated['total_vendido'];
                $porcentajeRetencion = (float) $validated['porcentaje_retencion'];
                $montoRetenido = round($totalVendido * ($porcentajeRetencion / 100), 2);
                $montoNetoPagado = round($totalVendido - $montoRetenido, 2);

                $pago = PagoVendedor::create([
                    'vendedor_id'          => (int) $validated['vendedor_id'],
                    'registrado_por_id'    => Auth::id(),
                    'total_vendido'        => $totalVendido,
                    'porcentaje_retencion' => $porcentajeRetencion,
                    'monto_retenido'       => $montoRetenido,
                    'monto_neto_pagado'    => $montoNetoPagado,
                    'metodo_pago'          => (string) $validated['metodo_pago'],
                    'notas'                => $validated['notas'] ?? null,
                ]);

                DB::afterCommit(function () use ($pago): void {
                    self::notificarLiquidacionWhatsApp($pago);
                });
            });

            return redirect()->back()->with('success', '¡Liquidación de vendedor registrada exitosamente y comprobante enviado por WhatsApp!');
        } catch (Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar la liquidación: ' . $e->getMessage());
        }
    }

    /**
     * Encola el comprobante de liquidación por WhatsApp si el vendedor tiene teléfono registrado.
     */
    public static function notificarLiquidacionWhatsApp(PagoVendedor $pago): void
    {
        try {
            $vendedor = $pago->vendedor ?? User::find($pago->vendedor_id);
            $telefono = preg_replace('/[^0-9]/', '', (string) ($vendedor?->telefono ?? ''));

            if (strlen($telefono) < 10) {
                Log::info("Liquidación #{$pago->id} registrada sin envío de WhatsApp: el vendedor #{$pago->vendedor_id} ({$vendedor?->name}) no tiene teléfono registrado.");
                return;
            }

            if (strlen($telefono) === 10) {
                $telefono = '521' . $telefono;
            } elseif (strlen($telefono) === 12 && str_starts_with($telefono, '52')) {
                $telefono = '521' . substr($telefono, 2);
            }

            $nombreVendedor  = $vendedor?->name ?? 'Vendedor';
            $fechaActual     = ($pago->created_at ?? now())->format('d/m/Y H:i');
            $totalFormatted  = number_format((float) $pago->total_vendido, 2);
            $porcentajeFmt   = rtrim(rtrim(number_format((float) $pago->porcentaje_retencion, 2, '.', ''), '0'), '.');
            $retenidoFmt     = number_format((float) $pago->monto_retenido, 2);
            $netoFmt         = number_format((float) $pago->monto_neto_pagado, 2);
            $metodoFmt       = ucwords(str_replace('_', ' ', (string) $pago->metodo_pago));
            $notasTexto      = trim((string) ($pago->notas ?? ''));
            $bloqueNotas     = $notasTexto !== '' ? "\n📝 *Notas:* {$notasTexto}" : '';

            $mensajeWa = "💵 *COMPROBANTE DE LIQUIDACIÓN / NÓMINA*\n" .
                         "• Folio de Pago: #{$pago->id}\n" .
                         "• Vendedor: {$nombreVendedor}\n" .
                         "• Fecha: {$fechaActual}\n" .
                         "──────────────────────\n" .
                         "📈 *Total Vendido:* \${$totalFormatted}\n" .
                         "📉 *Retención Empresa ({$porcentajeFmt}%):* -\${$retenidoFmt}\n" .
                         "💰 *Neto Pagado:* *\${$netoFmt}*\n" .
                         "💳 *Método:* {$metodoFmt}{$bloqueNotas}\n" .
                         "──────────────────────\n" .
                         "✅ *Tu pago ha sido liquidado exitosamente por Administración.*";

            DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telefono,
                'mensaje'    => $mensajeWa,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Comprobante de liquidación #{$pago->id} encolado en WhatsApp para el vendedor {$nombreVendedor} ({$telefono}).");
        } catch (Throwable $eWa) {
            Log::error("Fallo no bloqueante al encolar WhatsApp de liquidación #{$pago->id}: " . $eWa->getMessage(), [
                'exception' => $eWa,
                'pago_id'   => $pago->id,
            ]);
        }
    }
}

