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
            if (!class_exists('\Google\Cloud\Vision\V1\ImageAnnotatorClient')) {
                throw new \Exception("La librería de Google Vision no está instalada en este servidor.");
            }
            if (!env('GOOGLE_APPLICATION_CREDENTIALS') || !file_exists(base_path(env('GOOGLE_APPLICATION_CREDENTIALS')))) {
                // Si falta la key json
                throw new \Exception("Falta el archivo de credenciales de Google Vision (google-credentials.json).");
            }

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

                    if (
                        strpos($fullText, $montoBuscado1) !== false ||
                        strpos($fullText, $montoBuscado2) !== false ||
                        strpos($fullText, $montoBuscado3) !== false
                    ) {
                        $notasAdicionales = "\n[✅ Verificado por IA: Monto encontrado en la imagen]";
                    } else {
                        $notasAdicionales = "\n[⚠️ ALERTA IA: El monto de $" . $request->monto . " no se detectó claramente en la imagen. Favor de revisar manual.]";
                    }
                }
            } else {
                $notasAdicionales = "\n[⚠️ ALERTA IA: No se pudo detectar ningún texto en la imagen.]";
            }

            $imageAnnotator->close();
        } catch (\Throwable $e) {
            \Log::error('Error de Google Vision: ' . $e->getMessage());
            $notasAdicionales = "\n[⚠️ IA fuera de línea o sin configurar. Revisión manual requerida.]";
        }

        // Concatenar notas del usuario con las notas del sistema
        $notasFinales = $request->notas ? $request->notas . $notasAdicionales : ltrim($notasAdicionales);

        $comprobante = Comprobante::create([
            'user_id' => Auth::id(),
            'monto' => $request->monto,
            'imagen' => $path,
            'notas' => $notasFinales,
            'status' => 'pendiente'
        ]);

        // Notificar a los administradores por WhatsApp
        $adminPhones = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })
            ->pluck('telefono')
            ->map(function ($num) {
                $num = preg_replace('/[^0-9]/', '', (string)$num);
                return (strlen($num) == 10) ? '521' . $num : $num;
            })
            ->filter()
            ->unique()
            ->toArray();

        if (empty($adminPhones)) {
            // Número por defecto en caso de no encontrar admins con teléfono
            $adminPhones = ['5212222153410'];
        }

        $clienteNombre = Auth::user()->name;
        $montoNotif = number_format($request->monto, 2);
        
        $mensajeAdmin = "*🔔 NUEVO COMPROBANTE DE PAGO*\n\n" .
                        "• *Cliente:* {$clienteNombre}\n" .
                        "• *Monto reportado:* \${$montoNotif}\n" .
                        "• *Notas:* " . ($request->notas ?: 'Sin notas') . "\n\n" .
                        "Ingresa al panel de administrador para revisar la imagen y aprobar el pago.";

        foreach ($adminPhones as $telAdmin) {
            \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telAdmin,
                'mensaje'    => $mensajeAdmin,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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
            'user_id' => $comprobante->user_id, // El cliente (para mantener consistencia en la vista de Entradas)
            'cliente_id' => $comprobante->user_id, // Cliente
            'articulo_id' => $articuloId,
            'precio_venta' => $comprobante->monto,
            'descripcion' => 'Aprobación de comprobante #' . $comprobante->id . ($comprobante->notas ? ' - ' . $comprobante->notas : ''),
            'fecha_generado' => now()
        ]);

        $comprobante->update(['status' => 'aprobado']);

        // Enviar notificación por WhatsApp al cliente si tiene teléfono
        if ($comprobante->user && $comprobante->user->telefono) {
            $telefonoCliente = $comprobante->user->telefono;
            // Limpiar y formatear número (ej. agregar 521 si es de 10 dígitos)
            $telefonoCliente = preg_replace('/[^0-9]/', '', $telefonoCliente);
            if (strlen($telefonoCliente) == 10) {
                $telefonoCliente = '521' . $telefonoCliente;
            }

            $montoFormateado = number_format($comprobante->monto, 2);

            $mensajeWa = "*✅ PAGO APROBADO - El bajón*\n\n" .
                "Hola *" . $comprobante->user->name . "*, te confirmamos que tu comprobante de pago por *\${$montoFormateado}* ha sido revisado y *aprobado* exitosamente.\n\n" .
                "Este monto ya ha sido abonado a tu cuenta y descontado de tu saldo pendiente.\n\n" .
                "¡Gracias por tu pago!\n" .
                "_Mensaje automático de El bajón_";

            \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telefonoCliente,
                'mensaje'    => $mensajeWa,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Comprobante aprobado, saldo actualizado y notificación enviada al cliente.');
    }

    // Función para que el admin rechace el comprobante
    public function rechazar($id)
    {
        $comprobante = Comprobante::findOrFail($id);

        if ($comprobante->status !== 'pendiente') {
            return back()->with('error', 'El comprobante ya fue procesado.');
        }

        $comprobante->update(['status' => 'rechazado']);

        // Enviar notificación por WhatsApp al cliente sobre el rechazo
        if ($comprobante->user && $comprobante->user->telefono) {
            $telefonoCliente = $comprobante->user->telefono;
            $telefonoCliente = preg_replace('/[^0-9]/', '', $telefonoCliente);
            if (strlen($telefonoCliente) == 10) {
                $telefonoCliente = '521' . $telefonoCliente;
            }

            $montoFormateado = number_format($comprobante->monto, 2);

            $mensajeWa = "*❌ COMPROBANTE RECHAZADO - El bajón*\n\n" .
                "Hola *" . $comprobante->user->name . "*, te informamos que tu comprobante de pago por *\${$montoFormateado}* no pudo ser validado y ha sido *rechazado*.\n\n" .
                "Esto puede suceder si la imagen no es legible, el monto no coincide, o el pago aún no se refleja en nuestra cuenta.\n" .
                "Por favor, revisa tu comprobante e intenta subir uno nuevo desde tu panel de usuario.\n\n" .
                "Si tienes dudas, contáctanos.\n" .
                "_Mensaje automático de El bajón_";

            \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telefonoCliente,
                'mensaje'    => $mensajeWa,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Comprobante rechazado correctamente y notificación enviada.');
    }
}
