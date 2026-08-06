<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $fillable = ['user_id', 'monto', 'imagen', 'status', 'notas'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
