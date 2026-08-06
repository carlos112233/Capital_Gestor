<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Comprobante;
use App\Models\Entrada;
use Illuminate\Support\Facades\Auth;

class ComprobanteController extends Controller
{
    // Función para que el cliente suba su comprobante
    public function store(Request $request)
    {
        $request->validate([
            'monto' => 'nullable|numeric|min:0',
            'imagen' => 'required|image|max:5120', // Max 5MB
            'notas' => 'nullable|string'
        ]);

        $path = $request->file('imagen')->store('comprobantes', 'public');

        Comprobante::create([
            'user_id' => Auth::id(),
            'monto' => $request->monto,
            'imagen' => $path,
            'notas' => $request->notas,
            'status' => 'pendiente'
        ]);

        return back()->with('success', 'Comprobante subido exitosamente. Espera a que un administrador lo verifique.');
    }

    // Función para que el admin apruebe el comprobante
    public function aprobar($id)
    {
        $comprobante = Comprobante::findOrFail($id);
        
        if ($comprobante->status !== 'pendiente') {
            return back()->with('error', 'El comprobante ya fue procesado.');
        }

        // Buscar el artículo "Pago saldado" o similar para asociarlo al cobro
        $articulo = \App\Models\Articulo::where('nombre', 'like', '%pago saldado%')->first();
        $articuloId = $articulo ? $articulo->id : null;

        // Registrar la entrada de capital en la tabla `entradas`
        Entrada::create([
            'user_id' => Auth::id(), // Admin
            'cliente_id' => $comprobante->user_id, // Cliente
            'articulo_id' => $articuloId,
            'precio_venta' => $comprobante->monto,
            'descripcion' => 'Aprobación de comprobante #' . $comprobante->id . ($comprobante->notas ? ' - ' . $comprobante->notas : ''),
            'fecha_generado' => now()
        ]);

        $comprobante->update(['status' => 'aprobado']);

        return back()->with('success', 'Comprobante aprobado y saldo actualizado.');
    }

    // Función para que el admin rechace el comprobante
    public function rechazar($id)
    {
        $comprobante = Comprobante::findOrFail($id);
        
        if ($comprobante->status !== 'pendiente') {
            return back()->with('error', 'El comprobante ya fue procesado.');
        }

        $comprobante->update(['status' => 'rechazado']);

        return back()->with('success', 'Comprobante rechazado correctamente.');
    }
}
