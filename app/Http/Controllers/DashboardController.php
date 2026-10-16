<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // <-- Importante
use App\Jobs\SendWhatsAppJob;

class DashboardController extends Controller
{
    /**
     * Enrutador único y centralizado del Dashboard.
     * Delega estrictamente según el rol activo del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard($request);
        }

        if ($user->hasRole('vendedor')) {
            return $this->vendedorDashboard($request);
        }

        if ($user->hasRole('cliente')) {
            return $this->clienteDashboard($request);
        }

        return $this->clienteDashboard($request);
    }

    /**
     * Método puente para compatibilidad con rutas nombradas previas de Admin.
     */
    public function indexAdmin(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            return redirect()->route('dashboard');
        }
        return $this->adminDashboard($request);
    }

    /**
     * Método puente para compatibilidad con rutas previas de Cliente.
     */
    public function indexUsuario(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Dashboard exclusivo para Administradores.
     */
    private function adminDashboard(Request $request)
    {
        $resumen = User::select(
                'id', 'name', 'email', 'telefono', 'updated_at', 'image',
                DB::raw('image IS NOT NULL as has_image')
            )
            ->withSaldos()
            ->get();

        $resumen = $resumen->map(function ($User) {
            $totalDeuda = (float) ($User->ventas_sum_total_venta ?? 0);
            $totalPagado = (float) ($User->entradas_sum_precio_venta ?? 0);
            $ventasCorte = (float) ($User->ventas_corte_sum ?? 0);

            $saldo = $totalDeuda - $totalPagado;
            $saldoAnterior = max(0, $ventasCorte - $totalPagado);
            $saldoActual = max(0, $saldo - $saldoAnterior);

            $User->total_deuda = $totalDeuda;
            $User->total_pagado = $totalPagado;
            $User->saldo = $saldo;
            $User->saldo_corte_anterior = $saldoAnterior;
            $User->saldo_corte_actual = $saldoActual;

            return $User;
        });

        $totalSaldo = $resumen->sum(fn($User) => $User->saldo);
        $resumen = $resumen->sortBy('name')->values();

        $articulos = \App\Models\Articulo::select('id', 'nombre', 'precio')->orderBy('nombre', 'asc')->get();
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'cliente');
        })->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['admin', 'vendedor', 'cocina']);
        })->orderBy('name')->get(['id', 'name']);

        $comprobantesPendientes = \App\Models\Comprobante::with('user')
            ->whereIn('status', ['procesando_pago', 'pendiente'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboardAdmin', compact('resumen', 'totalSaldo', 'articulos', 'users', 'comprobantesPendientes'));
    }

    /**
     * Dashboard exclusivo para Vendedores ("Panel de Vendedor" de solo lectura).
     */
    private function vendedorDashboard(Request $request)
    {
        $user = Auth::user();

        // Si un administrador supervisa, puede inspeccionar a cualquier vendedor vía ?vendedor_id=X
        $vendedor = $user;
        if ($user->hasRole('admin') && $request->filled('vendedor_id')) {
            $vendedor = User::findOrFail($request->input('vendedor_id'));
        }

        // 1. Total Vendido: Acumulado de ventas donde comprador_id = $vendedor->id
        $totalVendido = (float) \App\Models\Venta::where('comprador_id', $vendedor->id)->sum('total_venta');

        // 2. Retención Empresa (20% por defecto o configurada) y Ganancia Neta Vendedor (80%)
        $porcentajeRetencion = PagoVendedorController::getPorcentajeRetencion((int) $vendedor->id);
        $porcentajeGanancia = round(100.00 - $porcentajeRetencion, 2);
        $montoRetenido = round($totalVendido * ($porcentajeRetencion / 100), 2);
        $gananciaNeta = round($totalVendido * (1 - ($porcentajeRetencion / 100)), 2);

        $porcentajeComision = $porcentajeGanancia;
        $comisionEstimada = $gananciaNeta;

        // 3. Pagos Recibidos: Historial de liquidaciones emitidas por el admin
        $historialPagos = \App\Models\PagoVendedor::with('registradoPor:id,name')
            ->where('vendedor_id', $vendedor->id)
            ->latest()
            ->get();

        $totalPagadoNeto = (float) $historialPagos->sum('monto_neto_pagado');
        $totalBaseLiquidada = (float) $historialPagos->sum('total_vendido');

        // 4. Saldo Pendiente por Cobrar (basado en el neto acumulado del 80% menos pagos recibidos)
        $saldoPendienteCobro = max(0.0, $gananciaNeta - $totalPagadoNeto);

        // Ventas recientes efectuadas por este vendedor
        $ventasRecientes = \App\Models\Venta::with(['user:id,name', 'articulo:id,nombre'])
            ->where('comprador_id', $vendedor->id)
            ->latest()
            ->take(15)
            ->get();

        // Lista de vendedores si el Admin está visualizando
        $vendedores = $user->hasRole('admin')
            ? User::whereHas('roles', fn($q) => $q->where('name', 'vendedor'))->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('dashboardVendedor', compact(
            'vendedor',
            'totalVendido',
            'porcentajeRetencion',
            'porcentajeGanancia',
            'montoRetenido',
            'gananciaNeta',
            'porcentajeComision',
            'comisionEstimada',
            'totalPagadoNeto',
            'totalBaseLiquidada',
            'saldoPendienteCobro',
            'historialPagos',
            'ventasRecientes',
            'vendedores'
        ));
    }

    /**
     * Dashboard exclusivo para Clientes.
     */
    private function clienteDashboard(Request $request)
    {
        $user = Auth::user();

        // Calculamos totales utilizando los accessors contables centralizados
        $totalDeuda = $user->total_deuda;
        $totalPagado = $user->total_pagado;
        // En la vista del cliente, saldo positivo significa a favor y negativo adeudo
        $saldo = $totalPagado - $totalDeuda;

        // Creamos un "resumen" para la vista, usando la misma estructura que la tabla
        $resumen = collect([
            (object)[
                'id' => $user->id,
                'nombre' => $user->name,
                'total_deuda' => $totalDeuda,
                'total_pagado' => $totalPagado,
                'saldo' => $saldo,
                'saldo_corte_anterior' => $user->saldo_corte_anterior,
                'saldo_corte_actual' => $user->saldo_corte_actual,
                'image_url' => $user->image_url,
            ]
        ]);

        $totalSaldo = $resumen->sum('saldo');

        $comprobantes = \App\Models\Comprobante::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact('resumen', 'totalSaldo', 'comprobantes'));
    }

    public function enviarRecordatoriosMasivos(Request $request)
    {
        $userIds = $request->input('user_ids');
        // Laravel convierte el JSON { "1": "50" } en un array asociativo [ 1 => 50 ]
        $ajustes = $request->input('ajustes', []);

        $usuarios = User::with(['ventas.articulo'])->whereIn('id', $userIds)->get();

        foreach ($usuarios as $user) {
            if (!$user->telefono) continue;

            $montoAjuste = isset($ajustes[$user->id]) ? (float)$ajustes[$user->id] : 0;

            // Generar PDF con el detalle de compras / estado de cuenta
            $pdfPath = null;
            try {
                $pdfPath = \App\Services\PdfReceiptService::generateEstadoCuentaPdf($user, $montoAjuste);
            } catch (\Exception $ePdf) {
                \Illuminate\Support\Facades\Log::error("Error generando PDF de estado de cuenta para Usuario #{$user->id}: " . $ePdf->getMessage());
            }

            if ($pdfPath && file_exists($pdfPath)) {
                $saldo = $user->saldo_pendiente - $montoAjuste;
                $saldoFormat = number_format($saldo, 2);
                $mensaje = "Hola *{$user->name}*, te compartimos tu Estado de Cuenta adjunto con el saldo a cubrir de *\${$saldoFormat}*.\n\nTransferir a cualquiera de estas cuentas bancarias.\nBBVA:\nCuenta: 158 086 7512\nCuenta CLABE: 012 650 01580867512 5\n\nMercado Pago:\nCuenta CLABE: 722969010384935035\n\nFavor de enviar tu comprobante de pago a este número de WhatsApp. ¡Gracias!";
            } else {
                $mensaje = $this->generarMensajeRecordatorio($user, $montoAjuste);
            }

            // Insertamos en la tabla para que el motor de Node.js de WhatsApp adjunte el PDF y envíe el texto completo
            DB::table('whatsapp_pending_messages')->insert([
                'numero' => $this->formatearNumero($user->telefono),
                'mensaje' => $mensaje,
                'pdf_path' => $pdfPath,
                'status' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Mensajes guardados con ajustes aplicados.']);
    }

    public function generarMensajeRecordatorio(User $user, float $montoAjuste = 0): string
    {
        $saldo = $user->saldo_pendiente - $montoAjuste;
        $saldoAnterior = $user->saldo_corte_anterior;
        $saldoActual = $user->saldo_corte_actual;

        $desgloseTexto = "";
        if ($saldoAnterior > 0) {
            $desgloseTexto .= "*Corte Anterior (Vencido):* $" . number_format($saldoAnterior, 2) . "\n" .
                "*Corte Actual (Quincena actual):* $" . number_format($saldoActual, 2) . "\n" .
                "--------------------------\n";
        }

        $ventasCliente = $user->ventas ? $user->ventas->filter(function ($venta) {
            return $venta->articulo && $venta->articulo->nombre !== 'Pago saldado';
        }) : collect();

        $ventasPorCubrir = collect();
        if ($saldo > 0 && $ventasCliente->isNotEmpty()) {
            $acumulado = 0.0;
            // Recorremos desde la venta más reciente hacia atrás para obtener solo lo que falta por pagar (FIFO)
            foreach ($ventasCliente->reverse() as $venta) {
                if ($acumulado >= $saldo - 0.01) {
                    break;
                }
                $ventasPorCubrir->prepend($venta);
                $acumulado += (float) $venta->total_venta;
            }
        }

        if ($ventasPorCubrir->isNotEmpty()) {
            $desgloseTexto .= "*Detalle de compras:*\n";
            foreach ($ventasPorCubrir as $venta) {
                $nombreArticulo = $venta->articulo ? $venta->articulo->nombre : 'Artículo';
                $desgloseTexto .= "- " . $venta->cantidad . "x " . $nombreArticulo .
                    ($venta->cantidad > 1 ? " ($" . number_format($venta->precio_venta, 2) . " c/u)" : "") .
                    " - $" . number_format($venta->total_venta, 2) . "\n";
            }
            $desgloseTexto .= "--------------------------\n";
        }

        $pdfLink = "";
        if (!empty($user->id)) {
            try {
                $pdfUrl = route('admin.clientes.estado-cuenta.pdf', $user->id);
                $pdfLink = "📄 *Estado de Cuenta PDF:* " . $pdfUrl . "\n\n";
            } catch (\Throwable $eUrl) {
                $pdfLink = "";
            }
        }

        return "Hola excelente tarde,  " . $user->name . ", solo es para informarte de tu saldo actual a cubrir es de *$" .
            number_format($saldo, 2) .
            "*\n\n" .
            $desgloseTexto .
            $pdfLink .
            "tienes dudas o deseas más informacion sobre el monto a cobrar de tu saldo, mandame un mensaje.\n\n" .
            "--------------------------\n" .
            "*DATOS PARA PAGO:*\n\n" .
            "*BBVA:*\n" .
            "Cuenta: *158 086 7512*\n" .
            "CLABE: *012 650 01580867512 5*\n\n" .
            "*Mercado Pago:*\n" .
            "CLABE: *722969010384935035*\n\n" .
            "--------------------------\n" .
            'Favor de enviar el comprobante a este número.';
    }

    // Función auxiliar para limpiar el número
    private function formatearNumero($num)
    {
        $num = preg_replace('/[^0-9]/', '', $num);
        return (strlen($num) == 10) ? '521' . $num : $num; // open-wa prefiere 521 para México
    }
}
