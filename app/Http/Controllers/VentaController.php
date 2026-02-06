<?php
namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\User;
use App\Models\Articulo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\NuevoPedidoNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException; // Importación necesaria

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        // PostgreSQL es sensible a mayúsculas en búsquedas (LIKE vs ILIKE)
        $ventasQuery = Venta::with(['user', 'articulo'])->latest();

        if (!$user->hasRole('admin')) {
            $ventasQuery->where('user_id', $user->id);
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $ventasQuery->whereHas('user', function ($query) use ($search) {
                // ILIKE es específico de Postgres para búsquedas insensibles a mayúsculas
                $query->where('name', 'ILIKE', '%' . $search . '%');
            });
        }

        $ventas = $ventasQuery->paginate(20);

        if ($request->ajax()) {
            return view('ventas._tabla', compact('ventas'))->render();
        }
        return view('ventas.index', compact('ventas'));
    }

    public function create(Request $request)
    {
        $articulos = Articulo::where('stock', '>', 0)->get(); // Mejora: solo artículos con stock
        $clientes = User::orderBy('name', 'asc')->get();
        $articuloId = $request->get('articulo_id');
        return view('ventas.create', compact('articulos', 'clientes', 'articuloId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad' => 'required|integer|min:1',
            'cliente_id' => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $articulo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                $cantidadVenta = (int) $validated['cantidad']; // Casteo explícito

                if ($articulo->stock < $cantidadVenta) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Stock insuficiente. Disponible: {$articulo->stock}",
                    ]);
                }

                $articulo->decrement('stock', $cantidadVenta);

                // Aseguramos que el total_venta sea tratado como float/decimal
                $totalVenta = $articulo->precio * $cantidadVenta;

                $venta = $articulo->ventas()->create([
                    'user_id'      => Auth::user()->hasRole('admin') ? $validated['cliente_id'] : Auth::id(),
                    'cantidad'     => $cantidadVenta,
                    'precio_venta' => $articulo->precio,
                    'total_venta'  => $totalVenta,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);

                if (!Auth::user()->hasRole('admin')) {
                    $pedido = Pedido::create([
                        'user_id'     => Auth::id(),
                        'articulo_id' => $articulo->id,
                        'descripcion' => $validated['descripcion'] ?? '',
                        'costo'       => $totalVenta,
                        'venta_id'    => $venta->id,
                        'cantidad'    => $cantidadVenta,
                    ]);

                    try {
                        Notification::route('mail', 'gestorcapital.0925@gmail.com')
                            ->notify(new NuevoPedidoNotification($pedido));
                    } catch (\Exception $e) {
                        \Log::error("Error notificación: " . $e->getMessage());
                    }
                }

                return redirect()->route('catalogo.index')->with('success', '¡Venta registrada!');
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::critical("Error en Store Venta: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error en base de datos.');
        }
    }


    // ... resto de métodos con lógica similar

     public function destroy(Venta $venta)
    {
        if (!Auth::user()->hasRole('admin') && $venta->user_id !== Auth::id()) {
            return $this->error('No autorizado', 403);
        }

        try {
            DB::transaction(function () use ($venta) {
                // CAMBIO POSTGRES: Usar findOrFail para asegurar el bloqueo en la transacción
                $articulo = Articulo::lockForUpdate()->find($venta->articulo_id);
                if ($articulo) {
                    $articulo->increment('stock', (int) $venta->cantidad);
                }
                $venta->delete();
            });

              return redirect()->route('ventas.index')->with('success', '¡Venta registrada!');
        } catch (\Exception $e) {
            \Log::error("Postgres Delete Error: " . $e->getMessage());
            return $this->error('Error al eliminar', 500);
        }
    }
}