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
        // Laravel convierte el JSON { "1": "50" } en un array asociativo [ 1 => 50 ]
        $ajustes = $request->input('ajustes', []);

        $usuarios = User::whereIn('id', $userIds)->get();

        foreach ($usuarios as $user) {
            if (!$user->telefono) continue;

            // Calculamos saldo base
            $totalDeuda = $user->ventas()->sum('total_venta') ?? 0;
            $totalPagado = $user->entradas()->sum('precio_venta') ?? 0;

            // CORRECCIÓN AQUÍ:
            // Buscamos el ajuste usando el ID del usuario como llave
            $montoAjuste = isset($ajustes[$user->id]) ? (float)$ajustes[$user->id] : 0;

            $saldo = $totalDeuda - $totalPagado - $montoAjuste;

            $mensaje = "Hola excelente tarde,  " . $user->name . ", solo es para informarte de tu saldo actual a cubrir es de *$" .
                number_format($saldo, 2) .
                "*\n tienes dudas o deseas más informacion sobre el monto a cobrar de tu saldo, mandame un mensaje.\n\n" .
                "--------------------------\n" .
                "*DATOS PARA PAGO:*\n\n" .
                "*BBVA:*\n" .
                "Cuenta: *158 086 7512*\n" .
                "CLABE: *012 650 01580867512 5*\n\n" .
                "*Mercado Pago:*\n" .
                "CLABE: *722969010384935035*\n\n" .
                "--------------------------\n" .
                'Favor de enviar el comprobante a este número.';

            // Insertamos en la tabla para que el motor de Node.js lo vea
            DB::table('whatsapp_pending_messages')->insert([
                'numero' => $this->formatearNumero($user->telefono),
                'mensaje' => $mensaje,
                'status' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Mensajes guardados con ajustes aplicados.']);
    }

    // Función auxiliar para limpiar el número
    private function formatearNumero($num)
    {
        $num = preg_replace('/[^0-9]/', '', $num);
        return (strlen($num) == 10) ? '521' . $num : $num; // open-wa prefiere 521 para México
    }
}
