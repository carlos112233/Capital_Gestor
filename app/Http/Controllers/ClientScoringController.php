<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Comprobante;
use App\Models\Venta;
use App\Models\Entrada;
use App\Services\ClientScoringService;
use Illuminate\Support\Facades\DB;

class ClientScoringController extends Controller
{
    /**
     * Devuelve los datos agregados para los gráficos de ApexCharts del Dashboard Admin.
     */
    public function getAnalyticsData()
    {
        // 1. Métricas clave
        $totalCobrado = (float) Entrada::sum('precio_venta');
        $totalVentas = (float) Venta::sum('total_venta');
        $saldoPendiente = max(0, $totalVentas - $totalCobrado);

        // 2. Conteo de scoring de clientes
        $clientes = User::whereHas('roles', function($q) {
            $q->where('name', 'cliente');
        })->get();

        $countVip = 0;
        $countRegular = 0;
        $countRiesgo = 0;

        foreach ($clientes as $cliente) {
            $scoring = ClientScoringService::getScoring($cliente);
            if ($scoring['tier'] === 'platino') {
                $countVip++;
            } elseif ($scoring['tier'] === 'regular') {
                $countRegular++;
            } else {
                $countRiesgo++;
            }
        }

        // 3. Estatus de comprobantes (Donut Chart)
        $comprobantesStats = [
            'aprobado' => Comprobante::where('status', 'aprobado')->count(),
            'procesando_pago' => Comprobante::where('status', 'procesando_pago')->count(),
            'rechazado' => Comprobante::where('status', 'rechazado')->count(),
            'pendiente' => Comprobante::where('status', 'pendiente')->count(),
        ];

        // 4. Tendencia mensual de ventas vs entradas (Últimos 6 meses)
        $meses = [];
        $ventasMensuales = [];
        $entradasMensuales = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->translatedFormat('M Y');
            $year = $date->year;
            $month = $date->month;

            $vSum = (float) Venta::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_venta');

            $eSum = (float) Entrada::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('precio_venta');

            $meses[] = ucfirst($monthName);
            $ventasMensuales[] = round($vSum, 2);
            $entradasMensuales[] = round($eSum, 2);
        }

        return response()->json([
            'kpi' => [
                'total_cobrado' => round($totalCobrado, 2),
                'saldo_pendiente' => round($saldoPendiente, 2),
                'count_vip' => $countVip,
                'count_regular' => $countRegular,
                'count_riesgo' => $countRiesgo,
            ],
            'donut' => $comprobantesStats,
            'trend' => [
                'categories' => $meses,
                'ventas' => $ventasMensuales,
                'entradas' => $entradasMensuales,
            ]
        ]);
    }

    /**
     * Actualiza manualmente la sobreescritura del score de un cliente.
     */
    public function updateManualScore(Request $request, $userId)
    {
        $request->validate([
            'override_score' => 'required|boolean',
            'score_manual' => 'nullable|integer|min:0|max:100',
            'notas_scoring' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        $user->update([
            'override_score' => $request->override_score,
            'score_manual' => $request->override_score ? $request->score_manual : null,
            'notas_scoring' => $request->notas_scoring,
        ]);

        $scoringUpdated = ClientScoringService::getScoring($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Scoring crediticio actualizado exitosamente.',
                'scoring' => $scoringUpdated,
            ]);
        }

        return redirect()->back()->with('success', 'Scoring crediticio de ' . $user->name . ' actualizado exitosamente.');
    }
}
