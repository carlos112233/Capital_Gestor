<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PagoVendedor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PagoVendedorController extends Controller
{
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

            return redirect()->back()->with('success', '¡Liquidación de vendedor registrada exitosamente!');
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

