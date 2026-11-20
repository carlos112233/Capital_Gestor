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

    public const CATEGORIA_CEMITA = 'cemita';
    public const CATEGORIA_MILANESA_POLLO = 'milanesa_pollo';
    public const CATEGORIA_MILANESA_PUERCO = 'milanesa_puerco';
    public const CATEGORIA_BEBIDA = 'bebida';
    public const CATEGORIA_OTRO = 'otro';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'img_base64',
        'img_tipo',
        'imagen_tipo',
        'disponible',
        'requiere_cocina',
        'categoria_cocina',
    ];

    protected $casts = [
        'disponible'      => 'boolean',
        'requiere_cocina' => 'boolean',
    ];

    protected $hidden = [
        'img_base64',
    ];

    protected $appends = [
        'imagen_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $articulo): void {
            if (is_null($articulo->categoria_cocina)) {
                $inferred = self::inferirMetadatosCocina(
                    (string) ($articulo->nombre ?? ''),
                    (string) ($articulo->descripcion ?? '')
                );
                $articulo->categoria_cocina = $inferred['categoria_cocina'];
                if (is_null($articulo->requiere_cocina)) {
                    $articulo->requiere_cocina = $inferred['requiere_cocina'];
                }
            } elseif (is_null($articulo->requiere_cocina)) {
                $articulo->requiere_cocina = $articulo->categoria_cocina !== self::CATEGORIA_BEBIDA;
            }
        });
    }

    /**
     * Clasificación determinista de respaldo al crear un artículo sin metadatos explícitos.
     *
     * @return array{requiere_cocina: bool, categoria_cocina: string}
     */
    public static function inferirMetadatosCocina(string $nombre, ?string $descripcion = null): array
    {
        $nombreLower = mb_strtolower(trim($nombre));
        $texto = mb_strtolower(trim($nombre . ' ' . ($descripcion ?? '')));

        if (in_array($nombreLower, ['pago saldado', 'abono', 'saldo'], true)) {
            return [
                'requiere_cocina'  => false,
                'categoria_cocina' => self::CATEGORIA_OTRO,
            ];
        }

        if (
            str_contains($texto, 'refresco')
            || str_contains($texto, 'coca')
            || str_contains($texto, 'agua')
            || str_contains($texto, 'jugo')
            || str_contains($texto, 'bebida')
        ) {
            return [
                'requiere_cocina'  => false,
                'categoria_cocina' => self::CATEGORIA_BEBIDA,
            ];
        }

        if (str_contains($texto, 'cemita') || str_contains($texto, 'milanesa')) {
            if (
                str_contains($texto, 'hawaiana')
                || str_contains($texto, 'cubana')
                || str_contains($texto, 'texana')
                || str_contains($texto, 'tejama')
            ) {
                return [
                    'requiere_cocina'  => true,
                    'categoria_cocina' => self::CATEGORIA_CEMITA,
                ];
            }

            if (str_contains($texto, 'pollo')) {
                return [
                    'requiere_cocina'  => true,
                    'categoria_cocina' => self::CATEGORIA_MILANESA_POLLO,
                ];
            }

            if (str_contains($texto, 'puerco') || str_contains($texto, 'cerdo') || str_contains($texto, 'milanesa')) {
                return [
                    'requiere_cocina'  => true,
                    'categoria_cocina' => self::CATEGORIA_MILANESA_PUERCO,
                ];
            }

            return [
                'requiere_cocina'  => true,
                'categoria_cocina' => self::CATEGORIA_CEMITA,
            ];
        }

        return [
            'requiere_cocina'  => true,
            'categoria_cocina' => self::CATEGORIA_OTRO,
        ];
    }

    /**
     * Determina si el artículo pertenece a la línea de cemitas/milanesas usando exclusivamente su categoría estructurada.
     */
    public function esCemitaOMilanesa(): bool
    {
        return in_array($this->categoria_cocina, [
            self::CATEGORIA_CEMITA,
            self::CATEGORIA_MILANESA_POLLO,
            self::CATEGORIA_MILANESA_PUERCO,
        ], true);
    }

    /**
     * Determina si el artículo requiere despacho a cocina como platillo general (no cemita).
     */
    public function requierePreparacionPlatilloCocina(): bool
    {
        return (bool) ($this->requiere_cocina ?? true) && !$this->esCemitaOMilanesa();
    }

    /**
     * Calcula las piezas de milanesa de pollo y puerco requeridas según categoria_cocina.
     *
     * @return array{pollo: int, puerco: int}
     */
    public function conteoMilanesas(int $cantidad): array
    {
        $qty = max(0, $cantidad);

        return match ($this->categoria_cocina) {
            self::CATEGORIA_CEMITA          => ['pollo' => $qty, 'puerco' => $qty],
            self::CATEGORIA_MILANESA_POLLO  => ['pollo' => $qty, 'puerco' => 0],
            self::CATEGORIA_MILANESA_PUERCO => ['pollo' => 0, 'puerco' => $qty],
            default                         => ['pollo' => 0, 'puerco' => 0],
        };
    }

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
