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

        // Guardamos la imagen localmente
        $path = $request->file('imagen')->store('comprobantes', 'public');
        $fullPath = storage_path('app/public/' . $path);

        $notasAdicionales = "";

        try {
            // Inicializar cliente de Google Vision
            $imageAnnotator = new \Google\Cloud\Vision\V1\ImageAnnotatorClient();

            // Leer la imagen guardada
            $imageContent = file_get_contents($fullPath);
            
            // Hacer la petición de detección de texto
            $response = $imageAnnotator->textDetection($imageContent);
            $texts = $response->getTextAnnotations();

            if (count($texts) > 0) {
                // El primer elemento contiene todo el texto detectado
                $fullText = strtolower($texts[0]->getDescription());
                
                // 1. Comprobar si parece un ticket bancario (buscar palabras clave)
                $keywords = ['bbva', 'mercado pago', 'transferencia', 'spei', 'pago', 'exitoso', 'autorización', 'folio', 'importe', 'monto', 'clabe', 'santander', 'banorte', 'stp'];
                $isTicket = false;
                foreach ($keywords as $kw) {
                    if (strpos($fullText, $kw) !== false) {
                        $isTicket = true;
                        break;
                    }
                }

                if (!$isTicket) {
                    // Borrar el archivo porque no es un ticket
                    Storage::disk('public')->delete($path);
                    $imageAnnotator->close();
                    return back()->with('error', 'La imagen subida no parece ser un comprobante bancario válido (no se detectaron palabras clave).');
                }

                // 2. Comprobar si el monto escrito por el cliente aparece en la imagen
                if ($request->monto) {
                    $montoBuscado1 = number_format($request->monto, 2, '.', ','); // ej: 1,500.00
                    $montoBuscado2 = number_format($request->monto, 2, '.', '');  // ej: 1500.00
                    $montoBuscado3 = (string) floatval($request->monto);          // ej: 1500

                    if (strpos($fullText, $montoBuscado1) !== false || 
                        strpos($fullText, $montoBuscado2) !== false || 
                        strpos($fullText, $montoBuscado3) !== false) {
                        $notasAdicionales = "\n[✅ Verificado por IA: Monto encontrado en la imagen]";
                    } else {
                        $notasAdicionales = "\n[⚠️ ALERTA IA: El monto de $" . $request->monto . " no se detectó claramente en la imagen. Favor de revisar manual.]";
                    }
                }

            } else {
                $notasAdicionales = "\n[⚠️ ALERTA IA: No se pudo detectar ningún texto en la imagen.]";
            }
            
            $imageAnnotator->close();

        } catch (\Exception $e) {
            \Log::error('Error de Google Vision: ' . $e->getMessage());
            $notasAdicionales = "\n[⚠️ Error interno de IA al verificar]";
        }

        // Concatenar notas del usuario con las notas del sistema
        $notasFinales = $request->notas ? $request->notas . $notasAdicionales : ltrim($notasAdicionales);

        Comprobante::create([
            'user_id' => Auth::id(),
            'monto' => $request->monto,
            'imagen' => $path,
            'notas' => $notasFinales,
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
