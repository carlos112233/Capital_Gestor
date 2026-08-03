<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $pendingMessages = DB::table('whatsapp_pending_messages')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('admin.configuracion.index', compact('pendingMessages'));
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension());
            $destinationPath = public_path('img');

            if ($extension === 'svg') {
                $file->move($destinationPath, 'Logo.svg');
            } else {
                $file->move($destinationPath, 'Logo.png');
                if (File::exists(public_path('img/Logo.svg'))) {
                    File::delete(public_path('img/Logo.svg'));
                }
            }

            return redirect()->back()->with('success', '¡El logotipo del sistema ha sido actualizado correctamente!');
        }

        return redirect()->back()->with('error', 'No se seleccionó ninguna imagen válida.');
    }

    /**
     * Obtener el estado detallado, nombre dinámico de la BD y lista de mensajes pendientes de WhatsApp
     */
    public function getWaStatus()
    {
        $statusPath = public_path('wa-status.json');
        $qrExists = File::exists(public_path('img/qr.png')) || File::exists(public_path('qr.png'));
        
        // Obtener el nombre real y dinámico de la Base de Datos conectada en PostgreSQL
        try {
            $realDbName = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $realDbName = config('database.connections.pgsql.database');
        }

        // Obtener la lista reciente de mensajes desde la tabla whatsapp_pending_messages
        try {
            $pendingMessages = DB::table('whatsapp_pending_messages')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();
        } catch (\Throwable $eMsgs) {
            $pendingMessages = [];
        }

        $responsePayload = [
            'status' => $qrExists ? 'qr_pendiente' : 'conectado',
            'message' => $qrExists ? 'Código QR listo para escanear' : 'WhatsApp vinculado y activo en el sistema.',
            'error_type' => null,
            'detail' => null,
            'solution_hint' => null,
            'db_name' => $realDbName,
            'qr_exists' => $qrExists,
            'messages' => $pendingMessages,
            'updated_at' => now()->toIso8601String()
        ];

        if (File::exists($statusPath)) {
            try {
                $json = json_decode(File::get($statusPath), true);
                if (is_array($json)) {
                    $json['qr_exists'] = $qrExists;
                    $json['db_name'] = $json['db_info']['database'] ?? $realDbName;
                    $json['messages'] = $pendingMessages;

                    if (($json['status'] ?? '') === 'qr_pendiente' && !$qrExists) {
                        $json['status'] = 'cargando';
                        $json['message'] = 'Generando imagen del código QR en el servidor...';
                    }

                    $responsePayload = $json;
                }
            } catch (\Throwable $e) {}
        }
        
        return response()->json($responsePayload)->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
    }

    /**
     * Borrar la sesión guardada de WhatsApp para forzar la emisión de un nuevo QR
     */
    public function resetWaSession()
    {
        try {
            // Eliminar carpetas de sesión local y cerrojos huérfanos
            $pathsToDelete = [
                base_path('.wwebjs_auth'),
                base_path('wa-motor/.wwebjs_auth'),
                public_path('.wwebjs_auth'),
                public_path('img/qr.png'),
                public_path('qr.png'),
                base_path('wa-motor/qr.png'),
                base_path('wa-motor/status.json'),
            ];

            foreach ($pathsToDelete as $p) {
                try {
                    if (File::isDirectory($p)) {
                        File::deleteDirectory($p);
                    } elseif (File::exists($p)) {
                        File::delete($p);
                    }
                } catch (\Throwable $eFile) {}
            }

            // Actualizar el archivo de estado de forma segura
            try {
                $statusPayload = [
                    'status' => 'cargando',
                    'message' => 'Sesión eliminada por el administrador. Generando nuevo código QR...',
                    'error_type' => null,
                    'detail' => null,
                    'solution_hint' => null,
                    'updated_at' => now()->toIso8601String()
                ];
                File::put(public_path('wa-status.json'), json_encode($statusPayload, JSON_PRETTY_PRINT));
            } catch (\Throwable $errWrite) {}

            // Asegurar que el motor esté corriendo sin matar Apache ni provocar OOM en 512MB RAM
            try {
                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    $check = shell_exec('pgrep -f "wa-motor"');
                    if (empty($check)) {
                        exec('nohup node ' . base_path('wa-motor/index.js') . ' > /dev/null 2>&1 < /dev/null &');
                    }
                }
            } catch (\Throwable $e) {}

            return redirect()->back()->with('success', '¡Sesión de WhatsApp eliminada correctamente! Generando un nuevo código QR...');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error eliminando la sesión de WhatsApp: ' . $e->getMessage());
        }
    }
}
