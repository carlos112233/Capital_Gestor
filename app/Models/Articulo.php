<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'img_base64',
        'img_tipo',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Scope para filtrar únicamente artículos comerciales (excluyendo artículos de flujo como 'Pago saldado' y 'Abono').
     */
    public function scopeComerciales($query)
    {
        return $query->whereNotIn('nombre', ['Pago saldado', 'Abono', 'pago saldado', 'abono']);
    }
}
