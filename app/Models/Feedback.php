<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'user_id',
        'tipo',
        'asunto',
        'mensaje',
        'imagen',
        'estatus',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(FeedbackMensaje::class, 'feedback_id')->oldest();
    }

    /**
     * Retorna clases de color de Tailwind para el badge de estatus.
     */
    public function getBadgeClassAttribute(): string
    {
        return match ($this->estatus) {
            'enviado' => 'bg-red-500/20 text-red-400 border border-red-500/30',
            'leyendo' => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
            'leido'   => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
            default   => 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
        };
    }

    /**
     * Retorna etiqueta legible en español para el estatus.
     */
    public function getEstatusLabelAttribute(): string
    {
        return match ($this->estatus) {
            'enviado' => 'Enviado (Sin ver por Admin)',
            'leyendo' => 'Leyendo / En Revisión',
            'leido'   => 'Leído / Resuelto / Cerrado',
            default   => 'Desconocido',
        };
    }

    /**
     * Envía una alerta automática por WhatsApp al Administrador al crearse un nuevo feedback.
     */
    public static function notificarAdminWhatsApp(self $feedback): void
    {
        try {
            $waService = app(\App\Services\WhatsAppNotificationService::class);
            $adminPhones = $waService->getAdminPhones();

            $feedback->loadMissing('user');
            $usuarioNombre = $feedback->user->name ?? 'Usuario del sistema';
            $tipoLabel = strtoupper($feedback->tipo);
            $asunto = $feedback->asunto ?: 'Sin asunto';
            $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';

            $mensajeWa = "*📢 NUEVO / A " . $tipoLabel . " - {$appName}*\n\n" .
                "• *Usuario:* {$usuarioNombre}\n" .
                "• *Tipo:* " . ucfirst($feedback->tipo) . "\n" .
                "• *Asunto:* {$asunto}\n" .
                "• *Mensaje:* {$feedback->mensaje}\n" .
                "• *Estado:* Enviado 🔴\n\n" .
                "_Ingresa al CRM en la sección Quejas y Sugerencias para revisarlo y responder._";

            $waService->enqueueToMultiple($adminPhones, $mensajeWa);
            Log::info("Notificación WhatsApp de Feedback #{$feedback->id} encolada para admins: " . implode(', ', $adminPhones));
        } catch (\Exception $e) {
            Log::error("Error encolando WhatsApp de Feedback #{$feedback->id}: " . $e->getMessage());
        }
    }
}
