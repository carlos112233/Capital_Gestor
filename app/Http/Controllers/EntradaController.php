<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Cliente;
use App\Models\Articulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- Importante
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;


class EntradaController extends Controller
{

    /**
     * Muestra una lista de las entradas.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $unMesAtras = Carbon::now()->subMonth();

        $entradasQuery = Entrada::with([
            'user:id,name',
            'articulo:id,nombre,precio',
            'cliente:id,name'
        ])
        ->where('created_at', '>=', $unMesAtras)
        ->latest();

        if (!$user->hasRole('admin')) {
            $entradasQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('cliente_id', $user->id);
            });
        }

        // Filtro de búsqueda global (usuario, cliente, artículo o descripción)
        if ($request->filled('q')) {
            $search = '%' . strtolower($request->input('q')) . '%';
            $entradasQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($query) use ($search) {
                    $query->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('cliente', function ($query) use ($search) {
                    $query->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('articulo', function ($query) use ($search) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$search]);
                })->orWhereRaw('LOWER(descripcion) LIKE ?', [$search]);
            });
        }

        $articulos = Articulo::select('id', 'nombre', 'precio')
            ->orderBy('nombre', 'asc')
            ->get();
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        if ($request->ajax()) {
            $entradas = $entradasQuery->get();
            return view('admin.entradas._tabla', compact('entradas', 'articulos', 'users'))->render();
        }

        $entradas = $entradasQuery->paginate(15)->withQueryString();
        return view('admin.entradas.index', compact('entradas', 'articulos', 'users'));
    }

    /**
     * Muestra el formulario para crear una nueva entrada.
     */
    public function create(): View
    {
        // Priorizar artículos financieros de abono/saldo en la selección
        $articulos = Articulo::whereIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
            ->orWhere('nombre', 'LIKE', '%saldo%')
            ->orderBy('nombre')
            ->get();

        if ($articulos->isEmpty()) {
            $articulos = Articulo::select('id', 'nombre', 'precio')
                ->orderBy('nombre')
                ->get();
        }

        // Solo traemos ID y nombre de los usuarios.
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.entradas.create', compact('articulos', 'users'));
    }

    /**
     * Guarda una nueva entrada en la base de datos.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos
        $validated = $request->validate([
            'cliente_id'   => 'nullable|exists:users,id',
            'articulo_id'  => 'required|exists:articulos,id',
            'precio_venta' => 'required|numeric',
            'descripcion'  => 'nullable|string|max:1000',
        ]);

        // 2. Determinar el cliente_id y user_id de forma segura
        $clienteId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
            ? $validated['cliente_id']
            : Auth::id();

        // 3. Crear el registro
        $data = [
            'articulo_id'    => $validated['articulo_id'],
            'user_id'        => $clienteId,
            'precio_venta'   => $validated['precio_venta'],
            'descripcion'    => $validated['descripcion'] ?? null,
            'fecha_generado' => now(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('entradas', 'cliente_id')) {
            $data['cliente_id'] = $clienteId;
        }

        $entrada = Entrada::create($data);

        // Enviar notificación de WhatsApp si es admin, se le registró a un cliente, y el checkbox enviar_wa está marcado
        $enviarWa = filter_var($request->input('enviar_wa', 1), FILTER_VALIDATE_BOOLEAN);

        \Illuminate\Support\Facades\Log::info("Debug WhatsApp Entrada #{$entrada->id}: enviarWa=" . ($enviarWa ? '1' : '0') . " | requestValue=" . json_encode($request->input('enviar_wa')) . " | isAdmin=" . (Auth::user()->hasRole('admin') ? '1' : '0') . " | clienteId={$clienteId} | authId=" . Auth::id());

        if ($enviarWa && Auth::user()->hasRole('admin')) {
            $cliente = User::find($clienteId);
            if ($cliente && !empty($cliente->telefono)) {
                try {
                    $num = preg_replace('/[^0-9]/', '', $cliente->telefono);
                    $telCliente = (strlen($num) == 10) ? '521' . $num : $num;
                    $totalFormatted = number_format($entrada->precio_venta, 2);
                    $reciboUrl = route('admin.entradas.show', $entrada);
                    $mensajeWa = "💰 ¡Hola {$cliente->name}! Se ha registrado tu Pago por la cantidad de \${$totalFormatted}.\n📄 Adjunto encontrarás tu Recibo de Pago en PDF.\n\n¡Gracias por tu pago en El Bajón!";

                    // Generar PDF temporal del recibo
                    $pdfPath = null;
                    try {
                        $pdfPath = \App\Services\PdfReceiptService::generateEntradaPdf($entrada);
                    } catch (\Exception $ePdf) {
                        \Illuminate\Support\Facades\Log::error("Error generando PDF para Entrada #{$entrada->id}: " . $ePdf->getMessage());
                    }

                    \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                        'numero'     => $telCliente,
                        'mensaje'    => $mensajeWa,
                        'pdf_path'   => $pdfPath,
                        'status'     => 'pendiente',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Web Push y Database Notification
                    $cliente->notify(new \App\Notifications\AppNotification(
                        'Pago Registrado 💰', 
                        "Se ha registrado tu Pago por \${$totalFormatted}.",
                        route('admin.entradas.show', $entrada)
                    ));

                    \Illuminate\Support\Facades\Log::info("Notificación WhatsApp de Pago (Entrada #{$entrada->id}) encolada para cliente: {$telCliente}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Entrada #{$entrada->id}: " . $e->getMessage());
                }
            }
        }

        // 4. Redireccionar
        return redirect()->route('admin.entradas.index')
            ->with('success', 'Entrada de capital registrada con éxito.');
    }

    /**
     * Muestra el comprobante/recibo de pago individual.
     */
    public function show(Entrada $entrada)
    {
        $entrada->load(['user', 'cliente', 'articulo']);
        return view('admin.entradas.show', compact('entrada'));
    }


    public function edit(Entrada $entrada)
    {
        $users = User::orderBy('name', 'asc')->get();
        $articulos = Articulo::whereIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
            ->orWhere('nombre', 'LIKE', '%saldo%')
            ->orderBy('nombre')
            ->get();

        if ($articulos->isEmpty()) {
            $articulos = Articulo::all();
        }
        $entrada->load(['user', 'cliente', 'articulo']);

        return view('admin.entradas.edit', compact('users', 'articulos', 'entrada'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        $validated = $request->validate([
            'articulo_id'  => 'required|exists:articulos,id',
            'cliente_id'   => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric',
            'descripcion'  => 'nullable|string|max:1000',
        ]);

        $clienteId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
            ? $validated['cliente_id']
            : ($entrada->cliente_id ?? $entrada->user_id ?? Auth::id());

        $data = [
            'articulo_id'    => $validated['articulo_id'],
            'user_id'        => $clienteId,
            'precio_venta'   => $validated['precio_venta'],
            'descripcion'    => $validated['descripcion'] ?? null,
            'fecha_generado' => Carbon::now(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('entradas', 'cliente_id')) {
            $data['cliente_id'] = $clienteId;
        }

        $entrada->update($data);

        return redirect()->route('admin.entradas.index')->with('success', 'Entrada de capital actualizada correctamente.');
    }

    public function destroy(Request $request, Entrada $entrada)
    {
        $cliente = $entrada->cliente ?? $entrada->user;
        $monto = number_format($entrada->precio_venta, 2);

        // Si el admin activó el checkbox opcional de WhatsApp en SweetAlert
        if ($request->filled('enviar_wa') && filter_var($request->enviar_wa, FILTER_VALIDATE_BOOLEAN) && $cliente && !empty($cliente->telefono)) {
            $num = preg_replace('/[^0-9]/', '', $cliente->telefono);
            $telCliente = (strlen($num) == 10) ? '521' . $num : $num;
            $mensajeWa = "⚠️ *ACTUALIZACIÓN DE PAGO - El bajón*\n\nHola *{$cliente->name}*, te informamos que tu registro de pago por *\${$monto}* ha sido revertido o no fue aprobado.\n\nFavor de comunicarte con administración si tienes alguna duda.";

            \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telCliente,
                'mensaje'    => $mensajeWa,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Enviar Notificación Push al cliente informando la reversión del pago
        if ($cliente) {
            \App\Services\PushNotificationService::notifyUser(
                $cliente,
                "Actualización de Pago ⚠️",
                "Su pago por \${$monto} no fue aprobado / fue revertido. Favor de consultar los detalles con administración.",
                route('dashboard')
            );
        }

        $entrada->delete();

        return redirect()->route('admin.entradas.index')
            ->with('success', 'Entrada de capital eliminada correctamente.');
    }

    /**
     * Reenvía el recibo de pago en PDF por WhatsApp al cliente.
     */
    public function reenviarWhatsApp(Entrada $entrada)
    {
        $cliente = $entrada->cliente ?? $entrada->user;
        if (!$cliente || empty($cliente->telefono)) {
            return redirect()->back()->with('error', 'El cliente no tiene un número de teléfono registrado.');
        }

        try {
            $num = preg_replace('/[^0-9]/', '', $cliente->telefono);
            $telCliente = (strlen($num) == 10) ? '521' . $num : $num;
            $totalFormatted = number_format($entrada->precio_venta, 2);
            $mensajeWa = "💰 ¡Hola {$cliente->name}! Se reenvía tu Recibo de Pago por la cantidad de \${$totalFormatted}.\n📄 Adjunto encontrarás tu Recibo de Pago en PDF.\n\n¡Gracias por tu pago en El Bajón!";

            // Generar PDF temporal del recibo
            $pdfPath = \App\Services\PdfReceiptService::generateEntradaPdf($entrada);

            \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telCliente,
                'mensaje'    => $mensajeWa,
                'pdf_path'   => $pdfPath,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', "Recibo en PDF encolado exitosamente para WhatsApp a {$cliente->name} ({$telCliente}).");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al encolar el PDF para WhatsApp: ' . $e->getMessage());
        }
    }
}
