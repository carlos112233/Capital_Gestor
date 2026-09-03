<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\FeedbackMensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Muestra la lista de Quejas, Comentarios y Sugerencias.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('admin');

        $query = Feedback::with(['user:id,name,email', 'mensajes'])->latest();

        // Filtrado por rol: El admin ve todos los mensajes, un usuario normal solo ve los suyos
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Filtro por búsqueda
        if ($request->filled('q')) {
            $search = '%' . strtolower($request->input('q')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(asunto) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(mensaje) LIKE ?', [$search])
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->whereRaw('LOWER(name) LIKE ?', [$search]);
                  });
            });
        }

        // Filtro por estatus ('enviado', 'leyendo', 'leido')
        if ($request->filled('estatus') && $request->input('estatus') !== 'todos') {
            $query->where('estatus', $request->input('estatus'));
        }

        // Filtro por tipo ('queja', 'comentario', 'sugerencia')
        if ($request->filled('tipo') && $request->input('tipo') !== 'todos') {
            $query->where('tipo', $request->input('tipo'));
        }

        $feedbacks = $query->paginate(20)->withQueryString();

        // Calcular estadísticas de tarjetas
        $baseStatsQuery = Feedback::query();
        if (!$isAdmin) {
            $baseStatsQuery->where('user_id', $user->id);
        }
        $stats = [
            'total'    => (clone $baseStatsQuery)->count(),
            'enviado'  => (clone $baseStatsQuery)->where('estatus', 'enviado')->count(),
            'leyendo'  => (clone $baseStatsQuery)->where('estatus', 'leyendo')->count(),
            'leido'    => (clone $baseStatsQuery)->where('estatus', 'leido')->count(),
        ];

        return view('feedback.index', compact('feedbacks', 'stats', 'isAdmin'));
    }

    /**
     * Procesa la creación de una nueva Queja, Comentario o Sugerencia.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipo'    => 'required|in:queja,comentario,sugerencia',
            'asunto'  => 'nullable|string|max:150',
            'mensaje' => 'required|string|max:4000',
            'imagen'  => 'nullable|image|max:5120', // Hasta 5 MB
        ]);

        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = 'feedback_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/feedbacks'), $filename);
            $imagenPath = 'uploads/feedbacks/' . $filename;
        }

        $feedback = Feedback::create([
            'user_id' => Auth::id(),
            'tipo'    => $validated['tipo'],
            'asunto'  => $validated['asunto'] ?? ucfirst($validated['tipo']),
            'mensaje' => $validated['mensaje'],
            'imagen'  => $imagenPath,
            'estatus' => 'enviado', // Estado inicial rojo 🔴
        ]);

        // Enviar notificación instantánea por WhatsApp al Administrador
        Feedback::notificarAdminWhatsApp($feedback);

        // Notificación Push a los Administradores
        $admins = User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->get();
        if ($admins->count() > 0) {
            try {
                Notification::send($admins, new AppNotification(
                    'Nueva ' . ucfirst($validated['tipo']),
                    Auth::user()->name . ' ha enviado un(a) ' . $validated['tipo'] . '.',
                    route('feedback.show', $feedback->id)
                ));
            } catch (\Exception $e) {
                Log::error('Error enviando push notification a admins (store): ' . $e->getMessage());
            }
        }

        return redirect()->route('feedback.index')->with('success', '¡Tu ' . $validated['tipo'] . ' se ha enviado correctamente! El Administrador la revisará pronto.');
    }

    /**
     * Muestra la conversación y detalles de un feedback.
     */
    public function show(Feedback $feedback): View
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('admin');

        // Permisos
        if (!$isAdmin && $feedback->user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver esta conversación.');
        }

        // TRANSICIÓN DE ESTATUS AUTOMÁTICA:
        // Si el Administrador abre un mensaje que estaba 'enviado' (rojo),
        // se cambia automáticamente a 'leyendo' (naranja 🟠)
        if ($isAdmin && $feedback->estatus === 'enviado') {
            $feedback->update(['estatus' => 'leyendo']);
            
            if ($feedback->user) {
                try {
                    $feedback->user->notify(new AppNotification(
                        'Ticket en revisión',
                        'El Administrador está revisando tu ' . $feedback->tipo . '.',
                        route('feedback.show', $feedback->id)
                    ));
                } catch (\Exception $e) {
                    Log::error('Error enviando push notification a usuario (show): ' . $e->getMessage());
                }
            }
        }

        $feedback->load(['user', 'mensajes.user']);

        return view('feedback.show', compact('feedback', 'isAdmin'));
    }

    /**
     * Añade un mensaje de respuesta en el hilo del feedback.
     */
    public function reply(Request $request, Feedback $feedback): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('admin');

        if (!$isAdmin && $feedback->user_id !== $user->id) {
            abort(403, 'No tienes permiso para responder a esta conversación.');
        }

        $validated = $request->validate([
            'mensaje' => 'required|string|max:4000',
            'imagen'  => 'nullable|image|max:5120',
            'estatus' => 'nullable|in:enviado,leyendo,leido',
        ]);

        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = 'reply_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/feedbacks'), $filename);
            $imagenPath = 'uploads/feedbacks/' . $filename;
        }

        FeedbackMensaje::create([
            'feedback_id' => $feedback->id,
            'user_id'     => $user->id,
            'mensaje'     => $validated['mensaje'],
            'imagen'      => $imagenPath,
        ]);

        // Gestión de estatus según quién responde
        if ($isAdmin) {
            // El administrador puede elegir marcarlo como 'leido' o dejarlo en 'leyendo'
            $nuevoEstatus = $request->input('estatus', 'leyendo'); // Por defecto naranja 🟠 al responder
            $feedback->update(['estatus' => $nuevoEstatus]);
            
            // Notificar al usuario normal
            if ($feedback->user) {
                try {
                    $feedback->user->notify(new AppNotification(
                        'Respuesta en tu ticket',
                        'El Administrador ha respondido a tu ' . $feedback->tipo . '.',
                        route('feedback.show', $feedback->id)
                    ));
                } catch (\Exception $e) {
                    Log::error('Error enviando push notification a usuario (reply): ' . $e->getMessage());
                }
            }
        } else {
            // Si el usuario vuelve a responder y estaba cerrado o leído, lo pasamos a 'enviado' para notificar
            if ($feedback->estatus === 'leido') {
                $feedback->update(['estatus' => 'enviado']);
            }
            
            // Notificar a los administradores
            $admins = User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->get();
            if ($admins->count() > 0) {
                try {
                    Notification::send($admins, new AppNotification(
                        'Nueva respuesta de ' . Auth::user()->name,
                        Auth::user()->name . ' ha respondido en el ticket #' . $feedback->id,
                        route('feedback.show', $feedback->id)
                    ));
                } catch (\Exception $e) {
                    Log::error('Error enviando push notification a admins (reply): ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('feedback.show', $feedback->id)->with('success', 'Mensaje enviado en la conversación.');
    }

    /**
     * Permite al Administrador cambiar el estatus del feedback manualmente (ej. Cerrar / Marcar como Leído).
     */
    public function updateStatus(Request $request, Feedback $feedback): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Acceso restringido a administradores.');
        }

        $validated = $request->validate([
            'estatus' => 'required|in:enviado,leyendo,leido',
        ]);

        $feedback->update(['estatus' => $validated['estatus']]);
        
        // Notificar al usuario sobre el cambio de estado
        if ($feedback->user) {
            try {
                $feedback->user->notify(new AppNotification(
                    'Actualización de tu ticket',
                    'El estado de tu ' . $feedback->tipo . ' ha cambiado a: ' . $feedback->estatus_label,
                    route('feedback.show', $feedback->id)
                ));
            } catch (\Exception $e) {
                Log::error('Error enviando push notification a usuario (updateStatus): ' . $e->getMessage());
            }
        }

        $label = $feedback->estatus_label;
        return redirect()->back()->with('success', "Estado actualizado a: {$label}");
    }
}
