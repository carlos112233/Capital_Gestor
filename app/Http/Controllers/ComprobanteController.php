<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Comprobante;
use App\Models\Entrada;
use App\Services\ReceiptOcrService;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ComprobanteController extends Controller
{
    /**
     * Permite al cliente subir su comprobante de pago con procesamiento OCR e IA.
     */
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

        // Procesamiento OCR e IA del comprobante
        $ocrResult = ReceiptOcrService::processImage($fullPath);
        $notasAdicionales = "";

        if (!$ocrResult['is_valid_receipt']) {
            Log::warning("Comprobante subido por usuario " . Auth::id() . " no pasó la validación estricta de palabras clave OCR.");
            $notasAdicionales .= "\n[⚠️ ALERTA OCR/IA: La imagen no contiene palabras clave claras de un comprobante bancario.]";
        } else {
            $bancoDetectado = $ocrResult['banco'] ?? 'No identificado';
            $montoExtraido = $ocrResult['monto_extraido'];

            $notasAdicionales .= "\n[🤖 OCR/IA: Banco: {$bancoDetectado}";
            if ($montoExtraido) {
                $notasAdicionales .= " | Monto Detectado: $" . number_format($montoExtraido, 2);
            }
            if (!empty($ocrResult['clave_rastreo'])) {
                $notasAdicionales .= " | Folio/Rastreo: " . $ocrResult['clave_rastreo'];
            }
            $notasAdicionales .= "]";

            if ($request->monto && $montoExtraido) {
                if (abs((float)$request->monto - (float)$montoExtraido) < 0.01) {
                    $notasAdicionales .= "\n[✅ Verificado por IA: El monto ingresado coincide exactamente con la imagen.]";
                } else {
                    $notasAdicionales .= "\n[⚠️ ALERTA IA: El monto reportado ($" . number_format($request->monto, 2) . ") difiere del detectado en imagen ($" . number_format($montoExtraido, 2) . ").]";
                }
            }
        }

        $notasFinales = $request->notas ? $request->notas . $notasAdicionales : ltrim($notasAdicionales);
        $montoFinal = $request->monto ?: ($ocrResult['monto_extraido'] ?? 0.00);

        // Crear el registro con estado 'procesando_pago'
        $comprobante = Comprobante::create([
            'user_id' => Auth::id(),
            'monto' => $montoFinal,
            'imagen' => $path,
            'notas' => $notasFinales,
            'status' => 'procesando_pago',
            'banco' => $ocrResult['banco'] ?? null,
            'clave_rastreo' => $ocrResult['clave_rastreo'] ?? null,
            'fecha_transferencia' => $ocrResult['fecha_transferencia'] ?? null,
            'clabe_cuenta' => $ocrResult['clabe_cuenta'] ?? null,
            'monto_extraido' => $ocrResult['monto_extraido'] ?? null,
            'datos_ocr_json' => json_encode($ocrResult),
        ]);

        // Notificar a administradores por WhatsApp
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
            $adminPhones = ['5212222153410'];
        }

        $clienteNombre = Auth::user()->name;
        $montoNotif = number_format($montoFinal, 2);
        
        $mensajeAdmin = "*🔔 NUEVO COMPROBANTE EN REVISIÓN (procesando_pago)*\n\n" .
                        "• *Cliente:* {$clienteNombre}\n" .
                        "• *Monto:* \${$montoNotif}\n" .
                        "• *Banco OCR:* " . ($ocrResult['banco'] ?? 'Por verificar') . "\n" .
                        "• *Folio/Rastreo:* " . ($ocrResult['clave_rastreo'] ?? 'No detectado') . "\n\n" .
                        "Ingresa al panel para aprobar o rechazar esta conciliación.";

        foreach ($adminPhones as $telAdmin) {
            DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telAdmin,
                'mensaje'    => $mensajeAdmin,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Notificar Push al cliente (Recepción formal)
        PushNotificationService::notifyUser(
            Auth::user(),
            "Comprobante en Revisión ⏳",
            "Está en proceso de revisión su pago; en un momento le informaremos si fue aceptado o rechazado su pago. Gracias por su preferencia.",
            route('dashboard')
        );

        // Notificar Push a administradores
        PushNotificationService::notifyAdmins(
            "Nuevo Comprobante 💳",
            "El usuario {$clienteNombre} subió un comprobante por \${$montoNotif}. Toca para revisar y conciliar.",
            route('cobros.index')
        );

        return back()->with('success', 'Comprobante recibido exitosamente. Su pago está en proceso de revisión.');
    }

    /**
     * Aprueba el comprobante y genera la entrada de capital en la base de datos.
     */
    public function aprobar($id)
    {
        $comprobante = Comprobante::findOrFail($id);

        if ($comprobante->status === 'aprobado') {
            return back()->with('error', 'El comprobante ya fue aprobado anteriormente.');
        }

        // Buscar el artículo "Pago saldado" o similar
        $articulo = \App\Models\Articulo::where('nombre', 'like', '%pago saldado%')->first();
        $articuloId = $articulo ? $articulo->id : null;

        // Registrar la entrada de capital
        Entrada::create([
            'user_id' => $comprobante->user_id,
            'cliente_id' => $comprobante->user_id,
            'articulo_id' => $articuloId,
            'precio_venta' => $comprobante->monto,
            'descripcion' => 'Aprobación de comprobante #' . $comprobante->id . ($comprobante->clave_rastreo ? ' [Folio: ' . $comprobante->clave_rastreo . ']' : '') . ($comprobante->banco ? ' [Banco: ' . $comprobante->banco . ']' : ''),
            'fecha_generado' => now()
        ]);

        $comprobante->update(['status' => 'aprobado']);

        // Notificar por WhatsApp al cliente
        if ($comprobante->user && $comprobante->user->telefono) {
            $telefonoCliente = $comprobante->user->telefono;
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

            DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telefonoCliente,
                'mensaje'    => $mensajeWa,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Notificar Push al cliente
        if ($comprobante->user) {
            $montoFormateado = number_format($comprobante->monto, 2);
            PushNotificationService::notifyUser(
                $comprobante->user,
                "Pago Aprobado 🟢",
                "Su pago por \${$montoFormateado} ha sido acreditado exitosamente. ¡Gracias por su preferencia!",
                route('dashboard')
            );
        }

        return back()->with('success', 'Comprobante aprobado, entrada de capital registrada y notificación enviada al cliente.');
    }

    /**
     * Rechaza el comprobante y opcionalmente notifica por WhatsApp si el admin lo requiere.
     */
    public function rechazar(Request $request, $id)
    {
        $comprobante = Comprobante::findOrFail($id);

        if ($comprobante->status === 'aprobado') {
            return back()->with('error', 'No se puede rechazar un comprobante que ya fue aprobado.');
        }

        $comprobante->update(['status' => 'rechazado']);

        // WhatsApp Opcional si el admin activó el checkbox (desactivado por defecto)
        $enviarWa = filter_var($request->input('enviar_wa', 0), FILTER_VALIDATE_BOOLEAN);

        if ($enviarWa && $comprobante->user && $comprobante->user->telefono) {
            $telefonoCliente = $comprobante->user->telefono;
            $telefonoCliente = preg_replace('/[^0-9]/', '', $telefonoCliente);
            if (strlen($telefonoCliente) == 10) {
                $telefonoCliente = '521' . $telefonoCliente;
            }

            $montoFormateado = number_format($comprobante->monto, 2);

            $mensajeWa = "*❌ COMPROBANTE NO APROBADO - El bajón*\n\n" .
                "Hola *" . $comprobante->user->name . "*, te informamos que tu comprobante de pago por *\${$montoFormateado}* no fue aprobado / fue rechazado tras la revisión.\n\n" .
                "Por favor, revisa tu comprobante e intenta subir uno nuevo desde tu panel de usuario o comunícate con administración.\n\n" .
                "_Mensaje automático de El bajón_";

            DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telefonoCliente,
                'mensaje'    => $mensajeWa,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Notificación Push SIEMPRE al cliente sobre actualización de pago
        if ($comprobante->user) {
            $montoFormateado = number_format($comprobante->monto, 2);
            PushNotificationService::notifyUser(
                $comprobante->user,
                "Actualización de Pago ⚠️",
                "Su pago por \${$montoFormateado} no fue aprobado / fue revertido. Favor de consultar los detalles con administración.",
                route('dashboard')
            );
        }

        return back()->with('success', 'Comprobante rechazado correctamente y notificación Push enviada.');
    }
}
