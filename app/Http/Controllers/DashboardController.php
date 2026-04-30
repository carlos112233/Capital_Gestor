<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // <-- Importante
use App\Jobs\SendWhatsAppJob;

class DashboardController extends Controller
{
    // Dashboard para administradores
    public function indexAdmin()
    {

        $resumen = User::withSum('ventas', 'total_venta')
            ->withSum('entradas', 'precio_venta')
            ->get()
            ->map(function ($User) {
                $totalDeuda = $User->ventas_sum_total_venta ?? 0;
                $totalPagado = $User->entradas_sum_precio_venta ?? 0;

                $User->total_deuda = $totalDeuda;
                $User->total_pagado = $totalPagado;
                $User->saldo =   $totalDeuda - $totalPagado;

                return $User;
            });
        $totalSaldo = $resumen->sum(function ($User) {
            return $User->saldo;
        });
        $resumen = $resumen->sortBy('name')->values();
        if (Auth::user()->hasRole('admin')) {
            return view('dashboardAdmin', compact('resumen', 'totalSaldo'));
        } else {
            redirect()->intended(route('dashboard'));
        }
    }

    // Dashboard para usuario normal
    public function indexUsuario()
    {
        $user = auth()->user();

        // Calculamos totales para el usuario logueado
        $totalDeuda = $user->ventas()->sum('total_venta');
        $totalPagado = $user->entradas()->sum('precio_venta');
        $saldo = $totalPagado - $totalDeuda;

        // Creamos un "resumen" para la vista, usando la misma estructura que tu tabla
        $resumen = collect([
            (object)[
                'id' => $user->id,
                'nombre' => $user->name,
                'total_deuda' => $totalDeuda,
                'total_pagado' => $totalPagado,
                'saldo' => $saldo,
            ]
        ]);

        // Sumatoria de todos los saldos (en este caso solo el suyo)
        $totalSaldo = $resumen->sum('saldo');

        if (!Auth::user()->hasRole('admin')) {
            return view('dashboard', compact('resumen', 'totalSaldo'));
        } else {
            return redirect()->intended(route('dashboardAdmin'));
            // o si prefieres directo:
            // return redirect()->route('dashboardAdmin');
        }
    }

    public function enviarRecordatoriosMasivos(Request $request)
    {
        $userIds = $request->input('user_ids');

        // 1. Log corregido (Este ya lo habías arreglado)
        \Log::info('Petición masiva recibida.', ['ids' => $userIds]);

        if (!$userIds || !is_array($userIds)) {
            return response()->json(['message' => 'No se seleccionaron usuarios válidos.'], 400);
        }

        $usuarios = User::whereIn('id', $userIds)->get();
        $retraso = 0;

        foreach ($usuarios as $user) {
            if (!$user->telefono) continue;

            $totalDeuda = $user->ventas()->sum('total_venta') ?? 0;
            $totalPagado = $user->entradas()->sum('precio_venta') ?? 0;
            $saldo = $totalDeuda - $totalPagado;

            $mensaje = "Hola " . $user->name . ", solo para informarte que tu saldo actual a cubrir es de *$" .
                number_format($saldo, 2) .
                "*\n si deseas más informacion el cobro de tu saldo, mandanos un mensaje.\n" .
                "--------------------------\n" .
                "*DATOS PARA PAGO:*\n\n" .
                "*BBVA:*\n" .
                "Cuenta: *158 086 7512*\n" .
                "CLABE: *012 650 01580867512 5*\n\n" .
                "*Mercado Pago:*\n" .
                "CLABE: *722969010384935035*\n\n" .
                "--------------------------\n" .
                'Favor de enviar el comprobante a este número.';

            // 2. CORRECCIÓN AQUÍ: El nombre del usuario debe ir dentro de un array []
            \Log::info('Procesando usuario para WhatsApp:', ['usuario' => $user->name]);

            // Despachamos el trabajo
            SendWhatsAppJob::dispatch($user, $mensaje)->delay(now()->addSeconds($retraso));

            $retraso += 15;
        }

        return response()->json(['message' => 'Se están enviando ' . count($usuarios) . ' mensajes.']);
    }
}
