<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrada extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'articulo_id',
        'precio_venta',
        'descripcion',
        'fecha_generado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id')->withTrashed();
    }

     public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }
}
