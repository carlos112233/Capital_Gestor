<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'image_tipo',
        'telefono', // Agregado para el Base64
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
         return $this->belongsToMany(Role::class)
                ->withPivot('user_id', 'role_id');
    }

      // --- AÑADE ESTA NUEVA FUNCIÓN ---
    public function entradas(): HasMany
    {
        return $this->hasMany(Entrada::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Total acumulado de deuda por compras del cliente.
     */
    public function getTotalDeudaAttribute(): float
    {
        if (array_key_exists('total_deuda', $this->attributes)) {
            return (float) $this->attributes['total_deuda'];
        }
        if (array_key_exists('ventas_sum_total_venta', $this->attributes)) {
            return (float) $this->attributes['ventas_sum_total_venta'];
        }
        return (float) $this->ventas()->sum('total_venta');
    }

    /**
     * Total acumulado de abonos y entradas de capital (pago saldado).
     */
    public function getTotalPagadoAttribute(): float
    {
        if (array_key_exists('total_pagado', $this->attributes)) {
            return (float) $this->attributes['total_pagado'];
        }
        if (array_key_exists('entradas_sum_precio_venta', $this->attributes)) {
            return (float) $this->attributes['entradas_sum_precio_venta'];
        }
        return (float) $this->entradas()->sum('precio_venta');
    }

    /**
     * Saldo real pendiente del cliente (Deuda - Pagado).
     */
    public function getSaldoPendienteAttribute(): float
    {
        if (array_key_exists('saldo', $this->attributes)) {
            return (float) $this->attributes['saldo'];
        }

        return $this->total_deuda - $this->total_pagado;
    }

    /**
     * Retorna la fecha límite del último corte quincenal (día 15 a las 23:59:59 o fin del mes anterior a las 23:59:59).
     */
    public function getFechaCorteAnteriorAttribute()
    {
        $hoy = \Carbon\Carbon::now();

        if ($hoy->day <= 15) {
            return $hoy->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
        }

        return $hoy->copy()->day(15)->endOfDay();
    }

    /**
     * Cuenta 1: Saldo del Corte Anterior (Deuda vencida de quincenas pasadas no pagada).
     */
    public function getSaldoCorteAnteriorAttribute(): float
    {
        if (array_key_exists('saldo_corte_anterior', $this->attributes)) {
            return (float) $this->attributes['saldo_corte_anterior'];
        }

        $ventasHastaCorte = array_key_exists('ventas_corte_sum', $this->attributes)
            ? (float) $this->attributes['ventas_corte_sum']
            : (float) $this->ventas()
                ->where('created_at', '<=', $this->fecha_corte_anterior)
                ->sum('total_venta');

        $totalPagado = array_key_exists('entradas_sum_precio_venta', $this->attributes)
            ? (float) $this->attributes['entradas_sum_precio_venta']
            : $this->total_pagado;

        $saldoAnterior = $ventasHastaCorte - $totalPagado;

        return (float) max(0, $saldoAnterior);
    }

    /**
     * Cuenta 2: Saldo del Corte Actual (Deuda generada en la quincena en curso tras cubrir el corte anterior).
     */
    public function getSaldoCorteActualAttribute(): float
    {
        if (array_key_exists('saldo_corte_actual', $this->attributes)) {
            return (float) $this->attributes['saldo_corte_actual'];
        }

        $saldoActual = $this->saldo_pendiente - $this->saldo_corte_anterior;

        return (float) max(0, $saldoActual);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }


}
