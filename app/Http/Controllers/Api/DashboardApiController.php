<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    // Dashboard ADMIN
    public function admin(Request $request)
    {
        $query = User::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            // Filtramos sobre la query inicial
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        // IMPORTANTE: Usamos $query, no User::
        $resumen = $query->withSum('ventas', 'total_venta')
            ->withSum('entradas', 'precio_venta')
            ->get()
            ->map(function ($user) {
                $totalDeuda = $user->ventas_sum_total_venta ?? 0;
                $totalPagado = $user->entradas_sum_precio_venta ?? 0;

                return [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'total_deuda' => $totalDeuda,
                    'total_pagado' => $totalPagado,
                    'saldo' => $totalDeuda - $totalPagado,
                ];
            })
            ->sortBy('nombre')
            ->values();

        return response()->json([
            'resumen' => $resumen,
            'totalSaldo' => $resumen->sum('saldo'),
        ]);
    }

    // Dashboard USUARIO NORMAL
    public function usuario()
    {
        $user = Auth::user();

        $totalDeuda = $user->ventas()->sum('total_venta');
        $totalPagado = $user->entradas()->sum('precio_venta');
        $saldo = $totalDeuda - $totalPagado;

        return response()->json([
            'resumen' => [
                [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'total_deuda' => $totalDeuda,
                    'total_pagado' => $totalPagado,
                    'saldo' => $saldo,
                ]
            ],
            'totalSaldo' => $saldo,
        ]);
    }
}
