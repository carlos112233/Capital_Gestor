<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PagoVendedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                PagoVendedor::create([
                    'vendedor_id'          => (int) $validated['vendedor_id'],
                    'registrado_por_id'    => Auth::id(),
                    'total_vendido'        => (float) $validated['total_vendido'],
                    'porcentaje_retencion' => (float) $validated['porcentaje_retencion'],
                    'monto_retenido'       => (float) $validated['monto_retenido'],
                    'monto_neto_pagado'    => (float) $validated['monto_neto_pagado'],
                    'metodo_pago'          => (string) $validated['metodo_pago'],
                    'notas'                => $validated['notas'] ?? null,
                ]);
            });

            return redirect()->back()->with('success', '¡Liquidación de vendedor registrada exitosamente!');
        } catch (Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar la liquidación: ' . $e->getMessage());
        }
    }
}

