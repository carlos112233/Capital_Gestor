<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasPushSubscriptions;

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
        'telefono',
        'score_calculado',
        'score_manual',
        'override_score',
        'notas_scoring',
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
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
        'avatar_url',
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
     * Obtiene la URL pública del avatar o un placeholder SVG por defecto.
     */
    public function getImageUrlAttribute(): string
    {
        if (!empty($this->image)) {
            if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                return $this->image;
            }

            if (Storage::disk('public')->exists($this->image)) {
                return asset('storage/' . ltrim($this->image, '/'));
            }
        }

        return asset('img/default_user.svg');
    }

    /**
     * Alias para compatibilidad con avatar_url.
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->image_url;
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('user_id', 'role_id');
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(Entrada::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function tutorials(): HasMany
    {
        return $this->hasMany(UserTutorial::class);
    }

    /**
     * Scope para cargar masivamente saldos y métricas de corte en 1 sola consulta SQL.
     * Erradica completamente el problema N+1 y aprovecha el índice compuesto de MySQL:
     * ventas: (user_id, created_at, total_venta)
     */
    public function scopeWithSaldos(Builder $query): Builder
    {
        $fechaCorte = static::calcularFechaCorteAnterior();

        return $query
            ->withSum('ventas as ventas_sum_total_venta', 'total_venta')
            ->withSum('entradas as entradas_sum_precio_venta', 'precio_venta')
            ->withSum(['ventas as ventas_corte_sum' => function ($q) use ($fechaCorte): void {
                $q->where('created_at', '<=', $fechaCorte);
            }], 'total_venta');
    }

    /**
     * Total acumulado de deuda por compras del cliente (con cacheo en instancia y soporte de relaciones cargadas).
     */
    public function getTotalDeudaAttribute(): float
    {
        if (array_key_exists('total_deuda', $this->attributes)) {
            return (float) $this->attributes['total_deuda'];
        }

        if (array_key_exists('ventas_sum_total_venta', $this->attributes)) {
            return (float) $this->attributes['ventas_sum_total_venta'];
        }

        // Si la relación ya está cargada en memoria, calcular en RAM sin emitir SQL
        if ($this->relationLoaded('ventas')) {
            $sum = (float) $this->ventas->sum('total_venta');
            $this->attributes['ventas_sum_total_venta'] = $sum;
            return $sum;
        }

        // Fallback bajo demanda con cacheo en el ciclo de vida del modelo
        $sum = (float) $this->ventas()->sum('total_venta');
        $this->attributes['ventas_sum_total_venta'] = $sum;

        return $sum;
    }

    /**
     * Total acumulado de abonos y entradas de capital (con cacheo en instancia y soporte de relaciones cargadas).
     */
    public function getTotalPagadoAttribute(): float
    {
        if (array_key_exists('total_pagado', $this->attributes)) {
            return (float) $this->attributes['total_pagado'];
        }

        if (array_key_exists('entradas_sum_precio_venta', $this->attributes)) {
            return (float) $this->attributes['entradas_sum_precio_venta'];
        }

        // Si la relación ya está cargada en memoria, calcular en RAM sin emitir SQL
        if ($this->relationLoaded('entradas')) {
            $sum = (float) $this->entradas->sum('precio_venta');
            $this->attributes['entradas_sum_precio_venta'] = $sum;
            return $sum;
        }

        // Fallback bajo demanda con cacheo
        $sum = (float) $this->entradas()->sum('precio_venta');
        $this->attributes['entradas_sum_precio_venta'] = $sum;

        return $sum;
    }

    /**
     * Saldo real pendiente del cliente (Deuda - Pagado).
     */
    public function getSaldoPendienteAttribute(): float
    {
        if (array_key_exists('saldo', $this->attributes)) {
            return (float) $this->attributes['saldo'];
        }

        if (array_key_exists('saldo_pendiente', $this->attributes)) {
            return (float) $this->attributes['saldo_pendiente'];
        }

        $saldo = (float) ($this->total_deuda - $this->total_pagado);
        $this->attributes['saldo_pendiente'] = $saldo;

        return $saldo;
    }

    /**
     * Retorna la fecha límite del último corte quincenal de forma estática sin sobrecarga de instanciación.
     */
    public static function calcularFechaCorteAnterior(): Carbon
    {
        $hoy = Carbon::now();

        if ($hoy->day <= 15) {
            return $hoy->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
        }

        return $hoy->copy()->day(15)->endOfDay();
    }

    /**
     * Accessor de fecha de corte anterior.
     */
    public function getFechaCorteAnteriorAttribute(): Carbon
    {
        return static::calcularFechaCorteAnterior();
    }

    /**
     * Cuenta 1: Saldo del Corte Anterior (Deuda vencida de quincenas pasadas no cubierta).
     */
    public function getSaldoCorteAnteriorAttribute(): float
    {
        if (array_key_exists('saldo_corte_anterior', $this->attributes)) {
            return (float) $this->attributes['saldo_corte_anterior'];
        }

        if (array_key_exists('ventas_corte_sum', $this->attributes)) {
            $ventasHastaCorte = (float) $this->attributes['ventas_corte_sum'];
        } elseif ($this->relationLoaded('ventas')) {
            $fechaCorte = $this->fecha_corte_anterior;
            $ventasHastaCorte = (float) $this->ventas
                ->filter(fn ($v) => $v->created_at !== null && $v->created_at <= $fechaCorte)
                ->sum('total_venta');
            $this->attributes['ventas_corte_sum'] = $ventasHastaCorte;
        } else {
            $ventasHastaCorte = (float) $this->ventas()
                ->where('created_at', '<=', $this->fecha_corte_anterior)
                ->sum('total_venta');
            $this->attributes['ventas_corte_sum'] = $ventasHastaCorte;
        }

        $totalPagado = $this->total_pagado;
        $saldoAnterior = (float) max(0, $ventasHastaCorte - $totalPagado);
        $this->attributes['saldo_corte_anterior'] = $saldoAnterior;

        return $saldoAnterior;
    }

    /**
     * Cuenta 2: Saldo del Corte Actual (Deuda generada en la quincena en curso tras cubrir el corte anterior).
     */
    public function getSaldoCorteActualAttribute(): float
    {
        if (array_key_exists('saldo_corte_actual', $this->attributes)) {
            return (float) $this->attributes['saldo_corte_actual'];
        }

        $saldoActual = (float) max(0, $this->saldo_pendiente - $this->saldo_corte_anterior);
        $this->attributes['saldo_corte_actual'] = $saldoActual;

        return $saldoActual;
    }

    /**
     * Check if the user has a specific role.
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

    /**
     * Obtiene la URL de la imagen del usuario (Foto de perfil o avatar SVG por defecto).
     */
    public function getImagenSrcAttribute(): string
    {
        return $this->image_url;
    }
}
