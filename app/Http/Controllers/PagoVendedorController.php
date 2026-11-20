<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PagoVendedor;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class PagoVendedorController extends Controller
{
    /**
     * Obtiene la configuración de porcentajes de retención respaldada en MySQL (app_settings y users).
     *
     * @return array{default: float, vendedores: array<string, float>}
     */
    public static function getRetencionConfig(): array
    {
        $default = 20.00;
        try {
            $settings = AppSetting::getSettings();
            if (isset($settings->retencion_default) && is_numeric($settings->retencion_default)) {
                $default = round((float) $settings->retencion_default, 2);
            }
        } catch (Throwable $e) {
            $default = 20.00;
        }

        $vendedores = [];
        try {
            if (Schema::hasColumn('users', 'porcentaje_retencion')) {
                $rows = DB::table('users')
                    ->whereNotNull('porcentaje_retencion')
                    ->get(['id', 'porcentaje_retencion']);

                foreach ($rows as $row) {
                    $vendedores[(string) $row->id] = round((float) $row->porcentaje_retencion, 2);
                }
            }
        } catch (Throwable $e) {
            // Fallback silencioso
        }

        return [
            'default'    => $default,
            'vendedores' => $vendedores,
        ];
    }

    /**
     * Devuelve el porcentaje de retención aplicable a un vendedor desde MySQL (por defecto 20%).
     */
    public static function getPorcentajeRetencion(?int $vendedorId = null): float
    {
        if ($vendedorId !== null && Schema::hasColumn('users', 'porcentaje_retencion')) {
            $pct = DB::table('users')
                ->where('id', $vendedorId)
                ->value('porcentaje_retencion');

            if ($pct !== null && is_numeric($pct)) {
                return round((float) $pct, 2);
            }
        }

        $config = self::getRetencionConfig();
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
            ->whereHas('roles', fn ($q) => $q->where('name', 'vendedor'))
            ->select('id', 'name', 'email', 'telefono', 'image', 'porcentaje_retencion', DB::raw('image IS NOT NULL as has_image'))
            ->withSum('comprasRealizadas as ventas_operador_sum', 'total_venta')
            ->withCount('comprasRealizadas as ventas_operador_count')
            ->withSum('pagosRecibidos as pagos_recibidos_sum', 'monto_neto_pagado')
            ->withSum('pagosRecibidos as base_liquidada_sum', 'total_vendido')
            ->orderBy('name')
            ->get()
            ->map(function (User $v) use ($retencionDefault) {
                $totalVendido = (float) ($v->ventas_operador_sum ?? 0);
                $totalPagadoNeto = (float) ($v->pagos_recibidos_sum ?? 0);
                $totalBaseLiquidada = (float) ($v->base_liquidada_sum ?? 0);

                $porcentajeRetencion = $v->porcentaje_retencion !== null
                    ? round((float) $v->porcentaje_retencion, 2)
                    : $retencionDefault;

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

        $kpis = [
            'total_vendido'   => (float) $vendedores->sum('total_vendido'),
            'monto_retenido'  => (float) $vendedores->sum('monto_retenido'),
            'ganancia_neta'   => (float) $vendedores->sum('ganancia_neta'),
            'total_pagado'    => (float) $vendedores->sum('total_pagado_neto'),
            'saldo_pendiente' => (float) $vendedores->sum('saldo_pendiente'),
        ];

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
     * Actualiza el porcentaje de retención global o individual de un vendedor en MySQL.
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

        $nuevoPorcentaje = round((float) $validated['porcentaje_retencion'], 2);

        if (!empty($validated['vendedor_id']) && !$request->boolean('aplicar_todos')) {
            $vendedor = User::findOrFail((int) $validated['vendedor_id']);
            $vendedor->forceFill([
                'porcentaje_retencion' => $nuevoPorcentaje,
            ])->save();

            $mensaje = "Porcentaje de retención para {$vendedor->name} actualizado al {$nuevoPorcentaje}% (Ganancia neta: " . (100 - $nuevoPorcentaje) . "%).";
        } else {
            $setting = AppSetting::first() ?? AppSetting::createDefaultInstance();
            $setting->retencion_default = $nuevoPorcentaje;
            $setting->save();
            AppSetting::clearSettingsCache();

            if ($request->boolean('aplicar_todos')) {
                DB::table('users')->whereNotNull('porcentaje_retencion')->update([
                    'porcentaje_retencion' => null,
                ]);
            }

            $mensaje = "Porcentaje de retención global actualizado al {$nuevoPorcentaje}% (Ganancia neta vendedor: " . (100 - $nuevoPorcentaje) . "%).";
        }

        return redirect()->route('admin.pagos-vendedores.index')
            ->with('success', $mensaje);
    }

    /**
     * Registra una liquidación de nómina/comisiones a un vendedor con protección contra doble liquidación.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada. Solo los administradores pueden autorizar y registrar liquidaciones.');
        }

        $validated = $request->validate([
            'vendedor_id'          => 'required|exists:users,id',
            'total_vendido'        => 'required|numeric|min:0.01',
            'porcentaje_retencion' => 'required|numeric|min:0|max:100',
            'monto_retenido'       => 'nullable|numeric|min:0',
            'monto_neto_pagado'    => 'nullable|numeric|min:0.01',
            'metodo_pago'          => 'required|string|max:50',
            'notas'                => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                $vendedorId = (int) $validated['vendedor_id'];

                // Bloqueo pesimista sobre el vendedor para impedir doble liquidación concurrente
                $vendedor = User::lockForUpdate()->findOrFail($vendedorId);

                $totalVentasHistoricas = (float) DB::table('ventas')
                    ->where('comprador_id', $vendedor->id)
                    ->sum('total_venta');

                $totalBaseYaLiquidada = (float) DB::table('pagos_vendedores')
                    ->where('vendedor_id', $vendedor->id)
                    ->sum('total_vendido');

                $basePendienteReal = round($totalVentasHistoricas - $totalBaseYaLiquidada, 2);
                $totalVendidoSolicitado = round((float) $validated['total_vendido'], 2);

                if ($basePendienteReal <= 0.00 || $totalVendidoSolicitado > $basePendienteReal + 0.01) {
                    throw ValidationException::withMessages([
                        'total_vendido' => "El vendedor no cuenta con saldo pendiente suficiente para liquidar (Base pendiente: \${$basePendienteReal}).",
                    ]);
                }

                $porcentajeRetencion = (float) $validated['porcentaje_retencion'];
                $montoRetenido = round($totalVendidoSolicitado * ($porcentajeRetencion / 100), 2);
                $montoNetoPagado = round($totalVendidoSolicitado - $montoRetenido, 2);

                $pago = PagoVendedor::create([
                    'vendedor_id'          => $vendedor->id,
                    'registrado_por_id'    => Auth::id(),
                    'total_vendido'        => $totalVendidoSolicitado,
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
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
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
            $waService = app(WhatsAppNotificationService::class);
            $vendedor = $pago->vendedor ?? User::find($pago->vendedor_id);
            $telefono = $waService->formatPhone((string) ($vendedor?->telefono ?? ''));

            if (strlen($telefono) < 10) {
                Log::info("Liquidación #{$pago->id} registrada sin envío de WhatsApp: el vendedor #{$pago->vendedor_id} ({$vendedor?->name}) no tiene teléfono registrado.");
                return;
            }

            $nombreVendedor = $vendedor?->name ?? 'Vendedor';
            $fechaActual    = ($pago->created_at ?? now())->format('d/m/Y H:i');
            $totalFormatted = number_format((float) $pago->total_vendido, 2);
            $porcentajeFmt  = rtrim(rtrim(number_format((float) $pago->porcentaje_retencion, 2, '.', ''), '0'), '.');
            $retenidoFmt    = number_format((float) $pago->monto_retenido, 2);
            $netoFmt        = number_format((float) $pago->monto_neto_pagado, 2);
            $metodoFmt      = ucwords(str_replace('_', ' ', (string) $pago->metodo_pago));
            $notasTexto     = trim((string) ($pago->notas ?? ''));
            $bloqueNotas    = $notasTexto !== '' ? "\n📝 *Notas:* {$notasTexto}" : '';

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

            $waService->enqueue($telefono, $mensajeWa);

            Log::info("Comprobante de liquidación #{$pago->id} encolado en WhatsApp para el vendedor {$nombreVendedor} ({$telefono}).");
        } catch (Throwable $eWa) {
            Log::error("Fallo no bloqueante al encolar WhatsApp de liquidación #{$pago->id}: " . $eWa->getMessage(), [
                'exception' => $eWa,
                'pago_id'   => $pago->id,
            ]);
        }
    }
}
