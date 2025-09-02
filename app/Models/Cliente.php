<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
     protected $fillable = [
        'nombre', 'email', 'telefono', 'direccion'
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

      public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }
}
