<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'imagen_tipo',
        'disponible',
    ];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    protected $hidden = [
        'img_base64',
    ];

    protected $appends = [
        'imagen_url',
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
     * Obtiene la URL pública del archivo en Storage o un placeholder SVG si no existe o está corrupto.
     */
    public function getImagenUrlAttribute(): string
    {
        if (!empty($this->img_base64)) {
            if (str_starts_with($this->img_base64, 'http://') || str_starts_with($this->img_base64, 'https://')) {
                return $this->img_base64;
            }

            if (Storage::disk('public')->exists($this->img_base64)) {
                return asset('storage/' . ltrim($this->img_base64, '/'));
            }
        }

        return asset('img/default_food.svg');
    }

    /**
     * Compatibilidad con llamadas heredadas a imagen_src.
     */
    public function getImagenSrcAttribute(): string
    {
        return $this->imagen_url;
    }
}
