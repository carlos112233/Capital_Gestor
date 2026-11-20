<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Entrada;
use App\Models\User;
use App\Models\Venta;
use App\Services\ClientScoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientScoringController extends Controller
{
    /**
     * Devuelve los datos agregados para los gráficos de ApexCharts del Dashboard Admin en complejidad O(1) de consultas SQL.
     */
    public function getAnalyticsData(): JsonResponse
    {
        // 1. Métricas clave globales
        $totalCobrado = (float) Entrada::sum('precio_venta');
        $totalVentas = (float) Venta::sum('total_venta');
        $saldoPendiente = max(0.0, $totalVentas - $totalCobrado);

        // 2. Conteo de scoring de clientes con precarga en bloque (Cero N+1)
        $clientes = User::whereHas('roles', function ($q) {
            $q->where('name', 'cliente');
        })
            ->select(['id', 'name', 'override_score', 'score_manual', 'score_calculado', 'notas_scoring'])
            ->withSaldos()
            ->get();

        $clienteIds = $clientes->pluck('id');

        $compStats = DB::table('comprobantes')
            ->whereIn('user_id', $clienteIds)
            ->selectRaw("user_id,
                SUM(CASE WHEN status = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                SUM(CASE WHEN status = 'rechazado' THEN 1 ELSE 0 END) as rechazados")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $pedidoStats = DB::table('pedidos')
            ->whereIn('user_id', $clienteIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $countVip = 0;
        $countRegular = 0;
        $countRiesgo = 0;

        foreach ($clientes as $cliente) {
            $totalDeuda = (float) ($cliente->ventas_sum_total_venta ?? 0);
            $totalPagado = (float) ($cliente->entradas_sum_precio_venta ?? 0);
            $saldo = max(0.0, $totalDeuda - $totalPagado);

            $scoring = ClientScoringService::getScoringFromData(
                $cliente,
                (int) ($compStats[$cliente->id]->aprobados ?? 0),
                (int) ($compStats[$cliente->id]->rechazados ?? 0),
                (int) ($pedidoStats[$cliente->id]->total ?? 0),
                $saldo
            );

            if ($scoring['tier'] === 'platino') {
                $countVip++;
            } elseif ($scoring['tier'] === 'regular') {
                $countRegular++;
            } else {
                $countRiesgo++;
            }
        }

        // 3. Estatus de comprobantes (Donut Chart) en 1 sola consulta agrupada
        $statusCounts = Comprobante::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $comprobantesStats = [
            'aprobado'        => (int) ($statusCounts['aprobado'] ?? 0),
            'procesando_pago' => (int) ($statusCounts['procesando_pago'] ?? 0),
            'rechazado'       => (int) ($statusCounts['rechazado'] ?? 0),
            'pendiente'       => (int) ($statusCounts['pendiente'] ?? 0),
        ];

        // 4. Tendencia mensual de ventas vs entradas (Últimos 6 meses) sin bucles SQL
        $inicioVentana = now()->subMonths(5)->startOfMonth();

        $ventasPorMes = DB::table('ventas')
            ->where('created_at', '>=', $inicioVentana)
            ->get(['created_at', 'total_venta'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('total_venta'));

        $entradasPorMes = DB::table('entradas')
            ->where('created_at', '>=', $inicioVentana)
            ->get(['created_at', 'precio_venta'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('precio_venta'));

        $meses = [];
        $ventasMensuales = [];
        $entradasMensuales = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $ymKey = $date->format('Y-m');
            $monthName = ucfirst($date->translatedFormat('M Y'));

            $meses[] = $monthName;
            $ventasMensuales[] = round((float) ($ventasPorMes[$ymKey] ?? 0.0), 2);
            $entradasMensuales[] = round((float) ($entradasPorMes[$ymKey] ?? 0.0), 2);
        }

        return response()->json([
            'kpi' => [
                'total_cobrado'   => round($totalCobrado, 2),
                'saldo_pendiente' => round($saldoPendiente, 2),
                'count_vip'       => $countVip,
                'count_regular'   => $countRegular,
                'count_riesgo'    => $countRiesgo,
            ],
            'donut' => $comprobantesStats,
            'trend' => [
                'categories' => $meses,
                'ventas'     => $ventasMensuales,
                'entradas'   => $entradasMensuales,
            ],
        ]);
    }

    /**
     * Actualiza manualmente la sobreescritura del score de un cliente.
     */
    public function updateManualScore(Request $request, $userId)
    {
        $request->validate([
            'override_score' => 'required|boolean',
            'score_manual'   => 'nullable|integer|min:0|max:100',
            'notas_scoring'  => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        $user->forceFill([
            'override_score' => (bool) $request->override_score,
            'score_manual'   => $request->override_score ? $request->score_manual : null,
            'notas_scoring'  => $request->notas_scoring,
        ])->save();

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
