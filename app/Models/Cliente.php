<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Cliente
 *
 * @deprecated Esta entidad y su tabla subyacente ('clientes') representan deuda técnica heredada y se encuentran en desuso.
 * Toda la lógica de negocio, autenticación, historial crediticio, estados de cuenta y saldos opera
 * centralizadamente a través del modelo App\Models\User con el rol 'cliente' (gestionado en la tabla 'role_user').
 * Se mantiene temporalmente para preservar compatibilidad con relaciones históricas en esquemas legados.
 */
class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'direccion',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(Entrada::class);
    }
}
