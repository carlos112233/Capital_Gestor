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
        
        // Obtener el driver y nombre real dinámico de la Base de Datos en uso (MySQL vs PostgreSQL)
        try {
            $dbDriver = DB::connection()->getDriverName();
            $dbDriverLabel = ($dbDriver === 'mysql') ? 'MySQL' : 'PostgreSQL';
            $realDbName = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $dbDriverLabel = 'PostgreSQL';
            $realDbName = config('database.connections.pgsql.database');
        }

        // Obtener la lista reciente de mensajes y conteos desde la tabla whatsapp_pending_messages
        try {
            $pendingMessages = DB::table('whatsapp_pending_messages')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();
            $pendingCount = DB::table('whatsapp_pending_messages')->where('status', 'pendiente')->count();
            $sentCount = DB::table('whatsapp_pending_messages')->where('status', 'enviado')->count();
        } catch (\Throwable $eMsgs) {
            $pendingMessages = [];
            $pendingCount = 0;
            $sentCount = 0;
        }

        $responsePayload = [
            'status' => $qrExists ? 'qr_pendiente' : 'conectado',
            'message' => $qrExists ? 'Código QR listo para escanear' : 'WhatsApp vinculado y activo en el sistema.',
            'error_type' => null,
            'detail' => null,
            'solution_hint' => null,
            'db_driver' => $dbDriverLabel,
            'db_name' => $realDbName,
            'qr_exists' => $qrExists,
            'messages' => $pendingMessages,
            'pending_count' => $pendingCount,
            'sent_count' => $sentCount,
            'updated_at' => now()->toIso8601String()
        ];

        // Auto-verificar que el motor Node.js esté activo en segundo plano
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $check = trim(shell_exec('pgrep -f "wa-motor/index.js" 2>/dev/null') ?? '');
                if (empty($check)) {
                    $nodeBin = trim(shell_exec('which node 2>/dev/null') ?? '') ?: '/usr/bin/node';
                    exec("nohup {$nodeBin} " . base_path('wa-motor/index.js') . ' > /dev/null 2>&1 < /dev/null &');
                }
            }
        } catch (\Throwable $e) {}

        if (File::exists($statusPath)) {
            try {
                $json = json_decode(File::get($statusPath), true);
                if (is_array($json)) {
                    $json['db_driver'] = $dbDriverLabel;
                    $json['db_name'] = $json['db_info']['database'] ?? $realDbName;
                    $json['messages'] = $pendingMessages;

                    // Si el motor indica que ya está conectado, forzar limpieza de QR obsoletos
                    if (($json['status'] ?? '') === 'conectado') {
                        $json['qr_exists'] = false;
                        @File::delete(public_path('img/qr.png'));
                        @File::delete(public_path('qr.png'));
                    } else {
                        $json['qr_exists'] = File::exists(public_path('img/qr.png')) || File::exists(public_path('qr.png'));
                        if ($json['qr_exists'] && ($json['status'] ?? '') !== 'error') {
                            $json['status'] = 'qr_pendiente';
                        }
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
            // 1. Matar o reiniciar el proceso de wa-motor (Soporte para PM2 y procesos nativos)
            try {
                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    shell_exec('pm2 restart wa-motor 2>/dev/null || pm2 restart index 2>/dev/null || pkill -9 -f "wa-motor" 2>/dev/null || pkill -9 -f "node.*index.js" 2>/dev/null');
                    sleep(1);
                }
            } catch (\Throwable $eKill) {}

            // 2. Eliminar carpetas de sesión local y archivos de estado / cerrojos huérfanos
            $pathsToDelete = [
                base_path('.wwebjs_auth'),
                base_path('wa-motor/.wwebjs_auth'),
                public_path('.wwebjs_auth'),
                public_path('img/qr.png'),
                public_path('qr.png'),
                base_path('wa-motor/qr.png'),
                base_path('wa-motor/status.json'),
                public_path('wa-status.json'),
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

            // 3. Actualizar el archivo de estado
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

            // 4. Iniciar limpiamente el motor Node en segundo plano
            try {
                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    $nodeBin = trim(shell_exec('which node 2>/dev/null') ?? '') ?: '/usr/bin/node';
                    exec("nohup {$nodeBin} " . base_path('wa-motor/index.js') . ' > /dev/null 2>&1 < /dev/null &');
                }
            } catch (\Throwable $e) {}

            return redirect()->back()->with('success', '¡Sesión de WhatsApp eliminada correctamente! Generando un nuevo código QR...');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error eliminando la sesión de WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Marcar un mensaje pendiente como enviado manualmente
     */
    public function markMessageAsSent($id)
    {
        try {
            DB::table('whatsapp_pending_messages')
                ->where('id', $id)
                ->update([
                    'status' => 'enviado',
                    'updated_at' => now(),
                ]);
            return response()->json(['success' => true, 'message' => 'Mensaje marcado como enviado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
