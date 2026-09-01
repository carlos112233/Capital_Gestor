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
        'disponible',
    ];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    protected $hidden = [
        'img_base64',
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

    /**
     * Obtiene la fuente de la imagen (Base64 o imagen por defecto).
     */
    public function getImagenSrcAttribute(): string
    {
        if (!empty($this->img_base64) && strlen($this->img_base64) > 100) {
            $tipo = $this->imagen_tipo ?: 'image/jpeg';
            return "data:{$tipo};base64,{$this->img_base64}";
        }
        return asset('img/default_food.svg');
    }
}
