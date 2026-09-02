<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $fillable = [
        'user_id',
        'monto',
        'imagen',
        'status',
        'notas',
        'banco',
        'clave_rastreo',
        'fecha_transferencia',
        'clabe_cuenta',
        'monto_extraido',
        'datos_ocr_json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
