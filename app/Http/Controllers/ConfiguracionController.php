<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class ConfiguracionController extends Controller
{
    protected function resolveNodeBinary(): ?string
    {
        $candidates = [
            env('WA_MOTOR_NODE_BIN'),
            env('NODE_BIN'),
            '/usr/local/bin/node',
            '/usr/bin/node',
            '/bin/node',
            '/opt/homebrew/bin/node',
        ];

        $candidates = array_filter($candidates, fn ($candidate) => is_string($candidate) && trim($candidate) !== '');

        foreach ($candidates as $candidate) {
            $normalized = trim((string) $candidate);
            $path = preg_replace('/\s+.+$/', '', $normalized) ?: $normalized;
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function resolveNpmBinary(): ?string
    {
        $candidates = [
            env('WA_MOTOR_NPM_BIN'),
            env('NPM_BIN'),
            '/usr/local/bin/npm',
            '/usr/bin/npm',
            '/bin/npm',
            '/opt/homebrew/bin/npm',
        ];

        $candidates = array_filter($candidates, fn ($candidate) => is_string($candidate) && trim($candidate) !== '');

        foreach ($candidates as $candidate) {
            $normalized = trim((string) $candidate);
            $path = preg_replace('/\s+.+$/', '', $normalized) ?: $normalized;
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function resolveMotorScriptPath(): ?string
    {
        $scriptPath = base_path('wa-motor/index.js');
        $realScript = realpath($scriptPath);

        if ($realScript === false || !is_file($realScript)) {
            return null;
        }

        $basePath = realpath(base_path());
        if ($basePath === false) {
            return null;
        }

        $normalizedBase = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realScript, $normalizedBase)) {
            return null;
        }

        return $realScript;
    }

    protected function ensureWaMotorIsRunning(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }

        $nodeBinary = $this->resolveNodeBinary();
        $scriptPath = $this->resolveMotorScriptPath();

        if ($nodeBinary === null || $scriptPath === null) {
            return;
        }

        $pgrep = Process::timeout(5)->run(['pgrep', '-f', 'wa-motor/index.js']);
        if ($pgrep->exitCode() === 0 && trim($pgrep->output()) !== '') {
            return;
        }

        $process = Process::path(dirname($scriptPath))->timeout(10)->start([$nodeBinary, basename($scriptPath)]);
        if (!$process->running()) {
            throw new \RuntimeException('No fue posible arrancar el motor WhatsApp con el binario configurado.');
        }
    }

    protected function stopWaMotor(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }

        Process::timeout(5)->run(['pkill', '-f', 'wa-motor/index.js']);
    }

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
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
        ], [
            'logo.required' => 'Debes seleccionar una imagen para el logotipo.',
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'Formatos permitidos: JPEG, PNG, JPG, GIF, SVG o WEBP.',
            'logo.max' => 'El logotipo no debe superar los 4MB.',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension());

            // 1. Sincronización con AppSetting (Almacenamiento Estándar)
            $setting = AppSetting::first() ?? new AppSetting();

            if (!empty($setting->logo_path) && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $logoPath = $file->store('branding', 'public');
            $setting->logo_path = $logoPath;
            $setting->save();

            // 2. Compatibilidad hacia atrás: copia a public/img/Logo.{ext}
            try {
                $destinationPath = public_path('img');
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
                $storedFullPath = Storage::disk('public')->path($logoPath);
                if (File::exists($storedFullPath)) {
                    if ($extension === 'svg') {
                        File::copy($storedFullPath, public_path('img/Logo.svg'));
                    } else {
                        File::copy($storedFullPath, public_path('img/Logo.png'));
                        if (File::exists(public_path('img/Logo.svg'))) {
                            @File::delete(public_path('img/Logo.svg'));
                        }
                    }
                }
            } catch (\Throwable $eLegacy) {
                // Silencioso para asegurar continuidad
            }

            // 3. Invalidación exhaustiva de caché
            Cache::forget('app_settings');
            AppSetting::clearSettingsCache();

            return redirect()->back()->with('success', '¡El logotipo del sistema ha sido actualizado y sincronizado correctamente!');
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
            $this->ensureWaMotorIsRunning();
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
            // 1. Matar o reiniciar el proceso de wa-motor sin depender de shell_exec/exec
            try {
                $this->stopWaMotor();
                sleep(1);
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

            // 4. Iniciar limpiamente el motor Node en segundo plano con validación de binarios
            try {
                $this->ensureWaMotorIsRunning();
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

    public function markAllMessagesAsSent()
    {
        try {
            $updated = DB::table('whatsapp_pending_messages')
                ->where('status', 'pendiente')
                ->update([
                    'status' => 'enviado',
                    'updated_at' => now(),
                ]);
            return response()->json(['success' => true, 'message' => "$updated mensajes marcados como enviados."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Marcar un mensaje enviado/fallido de vuelta a pendiente para reenviar
     */
    public function markMessageAsPending($id)
    {
        try {
            DB::table('whatsapp_pending_messages')
                ->where('id', $id)
                ->update([
                    'status' => 'pendiente',
                    'updated_at' => now(),
                ]);
            return response()->json(['success' => true, 'message' => 'Mensaje reencolado para envío correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
