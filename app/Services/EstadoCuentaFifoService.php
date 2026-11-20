<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entrada;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstadoCuentaFifoService
{
    /**
     * Obtiene la colección consolidada de usuarios con sus saldos de deuda, pagado y cortes quincenales.
     */
    public function obtenerResumenSaldosUsuarios(): Collection
    {
        return User::select(
            'id',
            'name',
            'email',
            'telefono',
            'updated_at',
            'image',
            DB::raw('image IS NOT NULL as has_image')
        )
            ->withSaldos()
            ->get()
            ->map(function (User $u) {
                $totalDeuda = (float) ($u->ventas_sum_total_venta ?? 0);
                $totalPagado = (float) ($u->entradas_sum_precio_venta ?? 0);
                $ventasCorte = (float) ($u->ventas_corte_sum ?? 0);

                $saldo = $totalDeuda - $totalPagado;
                $saldoAnterior = max(0.0, $ventasCorte - $totalPagado);
                $saldoActual = max(0.0, $saldo - $saldoAnterior);

                $u->total_deuda = $totalDeuda;
                $u->total_pagado = $totalPagado;
                $u->saldo = $saldo;
                $u->saldo_corte_anterior = $saldoAnterior;
                $u->saldo_corte_actual = $saldoActual;

                return $u;
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * Obtiene las compras más recientes pendientes de pago de un cliente hasta cubrir su saldo adeudado (FIFO).
     */
    public function obtenerVentasPendientesFifoUsuario(User $user, float $saldoPendiente): Collection
    {
        $ventasCliente = $user->ventas
            ? $user->ventas->filter(fn ($venta) => $venta->articulo && $venta->articulo->nombre !== 'Pago saldado')
            : collect();

        $ventasPorCubrir = collect();
        if ($saldoPendiente > 0 && $ventasCliente->isNotEmpty()) {
            $acumulado = 0.0;
            foreach ($ventasCliente->reverse() as $venta) {
                if ($acumulado >= $saldoPendiente - 0.01) {
                    break;
                }
                $ventasPorCubrir->prepend($venta);
                $acumulado += (float) $venta->total_venta;
            }
        }

        return $ventasPorCubrir;
    }

    /**
     * Calcula el estado FIFO ('PAGADO' | 'PENDIENTE') de todas las ventas pertenecientes a los usuarios indicados.
     *
     * @param iterable<int|string> $usuariosIds
     * @return array<int, string> Mapa [venta_id => 'PAGADO' | 'PENDIENTE']
     */
    public function calcularEstadosVentasFifo(iterable $usuariosIds): array
    {
        $ids = collect($usuariosIds)->filter()->unique()->values()->all();
        if (empty($ids)) {
            return [];
        }

        $entradasTotales = Entrada::whereIn('user_id', $ids)
            ->select('user_id', DB::raw('SUM(precio_venta) as total_entradas'))
            ->groupBy('user_id')
            ->pluck('total_entradas', 'user_id');

        $todasLasVentas = Venta::whereIn('user_id', $ids)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'user_id', 'total_venta']);

        $estadoVentas = [];
        foreach ($ids as $uid) {
            $saldo = (float) ($entradasTotales[$uid] ?? 0.0);
            $ventasDelUsuario = $todasLasVentas->where('user_id', $uid);

            foreach ($ventasDelUsuario as $vu) {
                $importe = (float) $vu->total_venta;
                if ($saldo >= $importe - 0.01) {
                    $estadoVentas[(int) $vu->id] = 'PAGADO';
                } else {
                    $estadoVentas[(int) $vu->id] = 'PENDIENTE';
                }
                $saldo -= $importe;
            }
        }

        return $estadoVentas;
    }

    /**
     * Construye el listado consolidado de movimientos FIFO, KPIs y series analíticas para la vista de Cobros.
     *
     * @return array{
     *   registros: Collection,
     *   totalCobrado: float,
     *   cobrosMes: float,
     *   cobrosPendientes: float,
     *   countVip: int,
     *   countRiesgo: int,
     *   chartCategories: array<int, string>,
     *   chartVentas: array<int, float>,
     *   chartCobros: array<int, float>
     * }
     */
    public function construirResumenCobros(User $user, ?string $search = null, string $estadoFiltro = 'todos'): array
    {
        $isAdmin = $user->hasRole('admin');
        $rangoFechaAdmin = Carbon::now()->subDays(30);
        $inicioMes = Carbon::now()->startOfMonth();

        // 1. Ventas con conciliación FIFO
        $ventasQuery = Venta::with(['user:id,name,email', 'articulo:id,nombre'])->latest();
        if ($isAdmin) {
            $ventasQuery->where('created_at', '>=', $rangoFechaAdmin);
        } else {
            $ventasQuery->where('user_id', $user->id)->where('created_at', '>=', $inicioMes);
        }

        if (!empty($search)) {
            $searchLower = '%' . strtolower($search) . '%';
            $ventasQuery->where(function ($q) use ($searchLower) {
                $q->whereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$searchLower]))
                  ->orWhereHas('articulo', fn ($a) => $a->whereRaw('LOWER(nombre) LIKE ?', [$searchLower]))
                  ->orWhereRaw('LOWER(descripcion) LIKE ?', [$searchLower]);
            });
        }

        $ventasObtenidas = $ventasQuery->get();
        $estadoVentas = $this->calcularEstadosVentasFifo($ventasObtenidas->pluck('user_id'));

        $ventas = $ventasObtenidas->map(fn ($v) => [
            'id'       => 'V-' . $v->id,
            'fecha'    => $v->created_at,
            'usuario'  => $v->user->name ?? 'Cliente Mostrador',
            'email'    => $v->user->email ?? 'N/A',
            'concepto' => $v->articulo->nombre ?? ($v->descripcion ?: 'Venta Directa'),
            'tipo'     => 'Venta Directa',
            'monto'    => (float) $v->total_venta,
            'estado'   => $estadoVentas[(int) $v->id] ?? 'PENDIENTE',
        ]);

        // 2. Entradas de capital
        $entradasQuery = Entrada::with(['user:id,name,email', 'articulo:id,nombre'])->latest();
        if ($isAdmin) {
            $entradasQuery->where('created_at', '>=', $rangoFechaAdmin);
        } else {
            $entradasQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id);
            })->where('created_at', '>=', $inicioMes);
        }

        if (!empty($search)) {
            $searchLower = '%' . strtolower($search) . '%';
            $entradasQuery->where(function ($q) use ($searchLower) {
                $q->whereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$searchLower]))
                  ->orWhereHas('articulo', fn ($a) => $a->whereRaw('LOWER(nombre) LIKE ?', [$searchLower]))
                  ->orWhereRaw('LOWER(descripcion) LIKE ?', [$searchLower]);
            });
        }

        $entradas = $entradasQuery->get()->map(fn ($e) => [
            'id'       => 'E-' . $e->id,
            'fecha'    => $e->created_at,
            'usuario'  => $e->user->name ?? 'Cliente',
            'email'    => $e->user->email ?? 'N/A',
            'concepto' => $e->articulo->nombre ?? ($e->descripcion ?: 'Entrada de Capital'),
            'tipo'     => 'Abono / Capital',
            'monto'    => (float) $e->precio_venta,
            'estado'   => 'PAGADO',
        ]);

        // 3. Pedidos
        $pedidosQuery = Pedido::with(['user:id,name,email', 'articulo:id,nombre', 'venta:id'])->latest();
        if ($isAdmin) {
            $pedidosQuery->where('created_at', '>=', $rangoFechaAdmin);
        } else {
            $pedidosQuery->where('user_id', $user->id)
                ->where(function ($query) use ($inicioMes) {
                    $query->whereNull('venta_id')
                          ->orWhere('created_at', '>=', $inicioMes);
                });
        }

        if (!empty($search)) {
            $searchLower = '%' . strtolower($search) . '%';
            $pedidosQuery->where(function ($q) use ($searchLower) {
                $q->whereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$searchLower]))
                  ->orWhereHas('articulo', fn ($a) => $a->whereRaw('LOWER(nombre) LIKE ?', [$searchLower]))
                  ->orWhereRaw('LOWER(descripcion) LIKE ?', [$searchLower]);
            });
        }

        $pedidos = $pedidosQuery->get()->map(fn ($p) => [
            'id'       => 'P-' . $p->id,
            'fecha'    => $p->created_at,
            'usuario'  => $p->user->name ?? 'Cliente',
            'email'    => $p->user->email ?? 'N/A',
            'concepto' => 'Pedido #' . $p->id . ': ' . ($p->articulo->nombre ?? 'Artículo'),
            'tipo'     => 'Pedido',
            'monto'    => (float) $p->costo,
            'estado'   => !is_null($p->venta_id) ? 'PAGADO' : 'PENDIENTE',
        ]);

        $todosLosRegistros = $ventas->concat($entradas)->concat($pedidos)->sortByDesc('fecha')->values();

        if ($estadoFiltro === 'pagado') {
            $todosLosRegistros = $todosLosRegistros->where('estado', 'PAGADO')->values();
        } elseif ($estadoFiltro === 'pendiente') {
            $todosLosRegistros = $todosLosRegistros->where('estado', 'PENDIENTE')->values();
        }

        $countVip = 0;
        $countRiesgo = 0;
        $chartCategories = [];
        $chartVentas = [];
        $chartCobros = [];

        if ($isAdmin) {
            $totalCobrado = (float) Venta::sum('total_venta') +
                            (float) Entrada::sum('precio_venta') +
                            (float) Pedido::whereNotNull('venta_id')->sum('costo');

            $cobrosMes = (float) Venta::where('created_at', '>=', $inicioMes)->sum('total_venta') +
                         (float) Entrada::where('created_at', '>=', $inicioMes)->sum('precio_venta') +
                         (float) Pedido::whereNotNull('venta_id')->where('created_at', '>=', $inicioMes)->sum('costo');

            $resumenUsuarios = User::select('id')
                ->withSum('ventas', 'total_venta')
                ->withSum('entradas', 'precio_venta')
                ->get();

            $cobrosPendientes = (float) $resumenUsuarios->sum(function ($u) {
                $totalDeuda = (float) ($u->ventas_sum_total_venta ?? 0);
                $totalPagado = (float) ($u->entradas_sum_precio_venta ?? 0);
                return max(0.0, $totalDeuda - $totalPagado);
            });

            $clientesScoring = User::whereHas('roles', fn ($q) => $q->where('name', 'cliente'))
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin', 'vendedor', 'cocina']))
                ->select(['id', 'name', 'override_score', 'score_manual', 'score_calculado', 'notas_scoring'])
                ->withSaldos()
                ->get();

            $scoringUserIds = $clientesScoring->pluck('id');

            $compStats = DB::table('comprobantes')
                ->whereIn('user_id', $scoringUserIds)
                ->selectRaw("user_id,
                    SUM(CASE WHEN status = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN status = 'rechazado' THEN 1 ELSE 0 END) as rechazados")
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $pedidoStats = DB::table('pedidos')
                ->whereIn('user_id', $scoringUserIds)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            foreach ($clientesScoring as $c) {
                $totalDeuda = (float) ($c->ventas_sum_total_venta ?? 0);
                $totalPagado = (float) ($c->entradas_sum_precio_venta ?? 0);
                $saldo = $totalDeuda - $totalPagado;

                $scoring = ClientScoringService::getScoringFromData(
                    $c,
                    (int) ($compStats[$c->id]->aprobados ?? 0),
                    (int) ($compStats[$c->id]->rechazados ?? 0),
                    (int) ($pedidoStats[$c->id]->total ?? 0),
                    $saldo
                );

                if ($scoring['tier'] === 'platino' || $scoring['score'] >= 80) {
                    $countVip++;
                } elseif ($scoring['tier'] === 'riesgo' || $scoring['score'] < 50) {
                    $countRiesgo++;
                }
            }

            $hace6Meses = now()->subMonths(5)->startOfMonth();

            $ventasAgrupadas = DB::table('ventas')
                ->where('created_at', '>=', $hace6Meses)
                ->get(['created_at', 'total_venta'])
                ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))
                ->map(fn ($rows) => (float) $rows->sum('total_venta'));

            $entradasAgrupadas = DB::table('entradas')
                ->where('created_at', '>=', $hace6Meses)
                ->get(['created_at', 'precio_venta'])
                ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'))
                ->map(fn ($rows) => (float) $rows->sum('precio_venta'));

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $ymKey = $date->format('Y-m');
                $label = ucfirst($date->translatedFormat('M Y'));

                $chartCategories[] = $label;
                $chartVentas[] = round((float) ($ventasAgrupadas[$ymKey] ?? 0.0), 2);
                $chartCobros[] = round((float) ($entradasAgrupadas[$ymKey] ?? 0.0), 2);
            }
        } else {
            $totalCobrado = (float) Venta::where('user_id', $user->id)->sum('total_venta') +
                            (float) Entrada::where(fn ($q) => $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id))->sum('precio_venta') +
                            (float) Pedido::where('user_id', $user->id)->whereNotNull('venta_id')->sum('costo');

            $cobrosMes = (float) Venta::where('user_id', $user->id)->where('created_at', '>=', $inicioMes)->sum('total_venta') +
                         (float) Entrada::where(fn ($q) => $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id))->where('created_at', '>=', $inicioMes)->sum('precio_venta') +
                         (float) Pedido::where('user_id', $user->id)->whereNotNull('venta_id')->where('created_at', '>=', $inicioMes)->sum('costo');

            $totalDeudaUser = (float) Venta::where('user_id', $user->id)->sum('total_venta');
            $totalPagadoUser = (float) Entrada::where(fn ($q) => $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id))->sum('precio_venta');
            $cobrosPendientes = max(0.0, $totalDeudaUser - $totalPagadoUser);
        }

        return [
            'registros'        => $todosLosRegistros,
            'totalCobrado'     => $totalCobrado,
            'cobrosMes'        => $cobrosMes,
            'cobrosPendientes' => $cobrosPendientes,
            'countVip'         => $countVip,
            'countRiesgo'      => $countRiesgo,
            'chartCategories'  => $chartCategories,
            'chartVentas'      => $chartVentas,
            'chartCobros'      => $chartCobros,
        ];
    }
}
