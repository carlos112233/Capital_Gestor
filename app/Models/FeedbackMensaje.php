<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FeedbackMensaje extends Model
{
    use HasFactory;

    protected $table = 'feedback_mensajes';

    protected $fillable = [
        'feedback_id',
        'user_id',
        'mensaje',
        'imagen',
    ];

    /**
     * Resuelve la URL pública del adjunto con retrocompatibilidad para rutas previas en uploads/feedbacks/.
     */
    public function getImagenUrlAttribute(): ?string
    {
        if (empty($this->imagen)) {
            return null;
        }

        if (str_starts_with($this->imagen, 'http://') || str_starts_with($this->imagen, 'https://')) {
            return $this->imagen;
        }

        if (str_starts_with($this->imagen, 'uploads/')) {
            return asset($this->imagen);
        }

        return Storage::disk('public')->url($this->imagen);
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
