<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        return view('dashboardAdmin', compact('resumen', 'totalSaldo'));
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

        return view('dashboard', compact('resumen', 'totalSaldo'));
    }
}
