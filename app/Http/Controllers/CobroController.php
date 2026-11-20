<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EstadoCuentaFifoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CobroController extends Controller
{
    public function __construct(
        private readonly EstadoCuentaFifoService $fifoService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->input('q');
        $estadoFiltro = (string) $request->input('estado', 'todos');
        $user = Auth::user();

        $resumen = $this->fifoService->construirResumenCobros(
            $user,
            $search ? (string) $search : null,
            $estadoFiltro
        );

        $todosLosRegistros = $resumen['registros'];
        $totalTransacciones = $todosLosRegistros->count();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageItems = $todosLosRegistros->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginador = new LengthAwarePaginator(
            $currentPageItems,
            $totalTransacciones,
            $perPage,
            $currentPage,
            [
                'path'  => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('cobros.index', [
            'paginador'          => $paginador,
            'totalCobrado'       => $resumen['totalCobrado'],
            'cobrosMes'          => $resumen['cobrosMes'],
            'cobrosPendientes'   => $resumen['cobrosPendientes'],
            'totalTransacciones' => $totalTransacciones,
            'countVip'           => $resumen['countVip'],
            'countRiesgo'        => $resumen['countRiesgo'],
            'chartCategories'    => $resumen['chartCategories'],
            'chartVentas'        => $resumen['chartVentas'],
            'chartCobros'        => $resumen['chartCobros'],
        ]);
    }
}
