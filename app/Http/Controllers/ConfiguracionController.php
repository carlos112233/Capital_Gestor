<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return view('admin.configuracion.index');
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
     * Obtener el estado detallado y diagnóstico de errores del motor de WhatsApp en JSON
     */
    public function getWaStatus()
    {
        $statusPath = public_path('wa-status.json');
        $qrExists = File::exists(public_path('img/qr.png')) || File::exists(public_path('qr.png'));
        
        if (File::exists($statusPath)) {
            try {
                $json = json_decode(File::get($statusPath), true);
                if (is_array($json)) {
                    $json['qr_exists'] = $qrExists;
                    
                    // Si el estado reporta qr_pendiente pero el archivo físico no existe aún, marcar como cargando
                    if (($json['status'] ?? '') === 'qr_pendiente' && !$qrExists) {
                        $json['status'] = 'cargando';
                        $json['message'] = 'Generando imagen del código QR en el servidor...';
                    }

                    return response()->json($json);
                }
            } catch (\Throwable $e) {}
        }
        
        return response()->json([
            'status' => $qrExists ? 'qr_pendiente' : 'conectado',
            'message' => $qrExists ? 'Código QR listo para escanear' : 'WhatsApp vinculado y activo en el sistema.',
            'error_type' => null,
            'detail' => null,
            'solution_hint' => null,
            'qr_exists' => $qrExists,
            'updated_at' => now()->toIso8601String()
        ]);
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
