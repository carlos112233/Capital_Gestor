<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
