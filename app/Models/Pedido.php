<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = [
        'user_id',
        'articulo_id',
        'descripcion',
        'costo',
        'cantidad',
        'venta_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
