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

        // Optimización de velocidad para la tabla: cargar transacciones de los últimos 30 días
        $rangoFecha = \Carbon\Carbon::now()->subDays(30);

        // 1. Obtener Ventas (Son cobros realizados / confirmados)
        $ventasQuery = Venta::with(['user:id,name,email', 'articulo:id,nombre'])->where('created_at', '>=', $rangoFecha)->latest();
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
        $ventas = $ventasQuery->get()->map(function ($v) {
            return [
                'id' => 'V-' . $v->id,
                'fecha' => $v->created_at,
                'usuario' => $v->user->name ?? 'Cliente Mostrador',
                'email' => $v->user->email ?? 'N/A',
                'concepto' => $v->articulo->nombre ?? ($v->descripcion ?: 'Venta Directa'),
                'tipo' => 'Venta Directa',
                'monto' => (float) $v->total_venta,
                'estado' => 'PAGADO',
            ];
        });

        // 2. Obtener Entradas de Capital (Pagos/Abonos recibidos)
        $entradasQuery = Entrada::with(['user:id,name,email', 'articulo:id,nombre'])
            ->where('created_at', '>=', $rangoFecha)
            ->latest();
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
        $pedidosQuery = Pedido::with(['user:id,name,email', 'articulo:id,nombre', 'venta:id'])->where('created_at', '>=', $rangoFecha)->latest();
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

        // Calcular Métricas KPI exactas desde base de datos para preservar integridad total de históricos
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
