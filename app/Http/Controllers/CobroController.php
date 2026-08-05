<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Entrada;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class CobroController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');
        $estadoFiltro = $request->input('estado', 'todos');

        $user = \Illuminate\Support\Facades\Auth::user();
        $isAdmin = $user && $user->hasRole('admin');

        // Para Admin: transacciones de los últimos 30 días
        // Para usuario estándar: pagos del mes en curso y cobros pendientes sin límite
        $rangoFechaAdmin = \Carbon\Carbon::now()->subDays(30);
        $inicioMes = \Carbon\Carbon::now()->startOfMonth();

        // 1. Obtener Ventas (Son cobros realizados / confirmados)
        $ventasQuery = Venta::with(['user:id,name,email', 'articulo:id,nombre'])->latest();
        if ($isAdmin) {
            $ventasQuery->where('created_at', '>=', $rangoFechaAdmin);
        } else {
            $ventasQuery->where('user_id', $user->id)
                        ->where('created_at', '>=', $inicioMes);
        }
        if ($search) {
            $searchLower = '%' . strtolower($search) . '%';
            $ventasQuery->where(function($q) use ($searchLower) {
                $q->whereHas('user', function($u) use ($searchLower) {
                    $u->whereRaw('LOWER(name) LIKE ?', [$searchLower]);
                })->orWhereHas('articulo', function($a) use ($searchLower) {
                    $a->whereRaw('LOWER(nombre) LIKE ?', [$searchLower]);
                })->orWhereRaw('LOWER(descripcion) LIKE ?', [$searchLower]);
            });
        }

        $ventasObtenidas = $ventasQuery->get();
        $usuariosIds = $ventasObtenidas->pluck('user_id')->filter()->unique();

        // 1.1 Calcular estado de cada Venta basado en Entradas (FIFO)
        $entradasTotales = Entrada::whereIn('user_id', $usuariosIds)
            ->select('user_id', DB::raw('SUM(precio_venta) as total_entradas'))
            ->groupBy('user_id')
            ->pluck('total_entradas', 'user_id');

        $todasLasVentas = Venta::whereIn('user_id', $usuariosIds)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'user_id', 'total_venta']);

        $estadoVentas = [];
        foreach ($usuariosIds as $uid) {
            $saldo = (float) ($entradasTotales[$uid] ?? 0);
            $ventasDelUsuario = $todasLasVentas->where('user_id', $uid);
            foreach ($ventasDelUsuario as $vu) {
                if ($saldo >= $vu->total_venta - 0.01) { // -0.01 for floating point safety
                    $estadoVentas[$vu->id] = 'PAGADO';
                    $saldo -= (float) $vu->total_venta;
                } else {
                    $estadoVentas[$vu->id] = 'PENDIENTE';
                    $saldo -= (float) $vu->total_venta;
                }
            }
        }

        $ventas = $ventasObtenidas->map(function ($v) use ($estadoVentas) {
            return [
                'id' => 'V-' . $v->id,
                'fecha' => $v->created_at,
                'usuario' => $v->user->name ?? 'Cliente Mostrador',
                'email' => $v->user->email ?? 'N/A',
                'concepto' => $v->articulo->nombre ?? ($v->descripcion ?: 'Venta Directa'),
                'tipo' => 'Venta Directa',
                'monto' => (float) $v->total_venta,
                'estado' => $estadoVentas[$v->id] ?? 'PENDIENTE',
            ];
        });

        // 2. Obtener Entradas de Capital (Pagos/Abonos recibidos)
        $entradasQuery = Entrada::with(['user:id,name,email', 'articulo:id,nombre'])->latest();
        if ($isAdmin) {
            $entradasQuery->where('created_at', '>=', $rangoFechaAdmin);
        } else {
            $entradasQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id);
            })->where('created_at', '>=', $inicioMes);
        }
        if ($search) {
            $searchLower = '%' . strtolower($search) . '%';
            $entradasQuery->where(function($q) use ($searchLower) {
                $q->whereHas('user', function($u) use ($searchLower) {
                    $u->whereRaw('LOWER(name) LIKE ?', [$searchLower]);
                })->orWhereHas('articulo', function($a) use ($searchLower) {
                    $a->whereRaw('LOWER(nombre) LIKE ?', [$searchLower]);
                })->orWhereRaw('LOWER(descripcion) LIKE ?', [$searchLower]);
            });
        }
        $entradas = $entradasQuery->get()->map(function ($e) {
            return [
                'id' => 'E-' . $e->id,
                'fecha' => $e->created_at,
                'usuario' => $e->user->name ?? 'Cliente',
                'email' => $e->user->email ?? 'N/A',
                'concepto' => $e->articulo->nombre ?? ($e->descripcion ?: 'Entrada de Capital'),
                'tipo' => 'Abono / Capital',
                'monto' => (float) $e->precio_venta,
                'estado' => 'PAGADO',
            ];
        });

        // 3. Obtener Pedidos (Pueden estar Pagados si tienen venta_id, o Pendientes si no)
        $pedidosQuery = Pedido::with(['user:id,name,email', 'articulo:id,nombre', 'venta:id'])->latest();
        if ($isAdmin) {
            $pedidosQuery->where('created_at', '>=', $rangoFechaAdmin);
        } else {
            $pedidosQuery->where('user_id', $user->id)
                ->where(function ($query) use ($inicioMes) {
                    $query->whereNull('venta_id') // Cobros pendientes sin límite de fecha
                          ->orWhere('created_at', '>=', $inicioMes); // O pagados en el mes en curso
                });
        }
        if ($search) {
            $searchLower = '%' . strtolower($search) . '%';
            $pedidosQuery->where(function($q) use ($searchLower) {
                $q->whereHas('user', function($u) use ($searchLower) {
                    $u->whereRaw('LOWER(name) LIKE ?', [$searchLower]);
                })->orWhereHas('articulo', function($a) use ($searchLower) {
                    $a->whereRaw('LOWER(nombre) LIKE ?', [$searchLower]);
                })->orWhereRaw('LOWER(descripcion) LIKE ?', [$searchLower]);
            });
        }
        $pedidos = $pedidosQuery->get()->map(function ($p) {
            $estaPagado = !is_null($p->venta_id);
            return [
                'id' => 'P-' . $p->id,
                'fecha' => $p->created_at,
                'usuario' => $p->user->name ?? 'Cliente',
                'email' => $p->user->email ?? 'N/A',
                'concepto' => 'Pedido #' . $p->id . ': ' . ($p->articulo->nombre ?? 'Artículo'),
                'tipo' => 'Pedido',
                'monto' => (float) $p->costo,
                'estado' => $estaPagado ? 'PAGADO' : 'PENDIENTE',
            ];
        });

        // Fusionar todos los registros
        $todosLosRegistros = $ventas->concat($entradas)->concat($pedidos)->sortByDesc('fecha')->values();

        // Aplicar filtro por estado
        if ($estadoFiltro === 'pagado') {
            $todosLosRegistros = $todosLosRegistros->where('estado', 'PAGADO')->values();
        } elseif ($estadoFiltro === 'pendiente') {
            $todosLosRegistros = $todosLosRegistros->where('estado', 'PENDIENTE')->values();
        }

        // Calcular Métricas KPI exactas desde base de datos
        if ($isAdmin) {
            $totalCobrado = (float) Venta::sum('total_venta') +
                            (float) Entrada::sum('precio_venta') +
                            (float) Pedido::whereNotNull('venta_id')->sum('costo');
            $inicioMes = now()->startOfMonth();
            $cobrosMes = (float) Venta::where('created_at', '>=', $inicioMes)->sum('total_venta') +
                         (float) Entrada::where('created_at', '>=', $inicioMes)->sum('precio_venta') +
                         (float) Pedido::whereNotNull('venta_id')->where('created_at', '>=', $inicioMes)->sum('costo');

            // Calcular Sumatoria a Favor igual que en el Dashboard Admin
            $resumenUsuarios = User::select('id')
                ->withSum('ventas', 'total_venta')
                ->withSum('entradas', 'precio_venta')
                ->get();
            $cobrosPendientes = $resumenUsuarios->sum(function ($u) {
                $totalDeuda = (float) ($u->ventas_sum_total_venta ?? 0);
                $totalPagado = (float) ($u->entradas_sum_precio_venta ?? 0);
                return max(0, $totalDeuda - $totalPagado);
            });
        } else {
            $totalCobrado = (float) Venta::where('user_id', $user->id)->sum('total_venta') +
                            (float) Entrada::where(function ($q) use ($user) { $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id); })->sum('precio_venta') +
                            (float) Pedido::where('user_id', $user->id)->whereNotNull('venta_id')->sum('costo');
            $inicioMes = now()->startOfMonth();
            $cobrosMes = (float) Venta::where('user_id', $user->id)->where('created_at', '>=', $inicioMes)->sum('total_venta') +
                         (float) Entrada::where(function ($q) use ($user) { $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id); })->where('created_at', '>=', $inicioMes)->sum('precio_venta') +
                         (float) Pedido::where('user_id', $user->id)->whereNotNull('venta_id')->where('created_at', '>=', $inicioMes)->sum('costo');

            $totalDeudaUser = (float) Venta::where('user_id', $user->id)->sum('total_venta');
            $totalPagadoUser = (float) Entrada::where(function ($q) use ($user) { $q->where('user_id', $user->id)->orWhere('cliente_id', $user->id); })->sum('precio_venta');
            $cobrosPendientes = max(0, $totalDeudaUser - $totalPagadoUser);
        }

        $totalTransacciones = $todosLosRegistros->count();

        // Paginación manual de la colección
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageItems = $todosLosRegistros->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $paginador = new LengthAwarePaginator(
            $currentPageItems,
            $todosLosRegistros->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('cobros.index', compact(
            'paginador',
            'totalCobrado',
            'cobrosMes',
            'cobrosPendientes',
            'totalTransacciones'
        ));
    }
}
