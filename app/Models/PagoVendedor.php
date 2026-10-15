<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVendedor extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'pagos_vendedores';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendedor_id',
        'registrado_por_id',
        'total_vendido',
        'porcentaje_retencion',
        'monto_retenido',
        'monto_neto_pagado',
        'metodo_pago',
        'notas',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_vendido' => 'decimal:2',
            'porcentaje_retencion' => 'decimal:2',
            'monto_retenido' => 'decimal:2',
            'monto_neto_pagado' => 'decimal:2',
        ];
    }

    /**
     * Vendedor al que corresponde la liquidación.
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    /**
     * Administrador u operador que autorizó el pago.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}

