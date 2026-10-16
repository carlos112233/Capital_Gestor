<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppSetting extends Model
{
    public const CACHE_KEY = 'app_settings';

    public const DEFAULT_APP_NAME = 'El bajón';
    public const DEFAULT_PRIMARY_COLOR = '#6366f1';
    public const DEFAULT_SECONDARY_COLOR = '#10b981';
    public const DEFAULT_SIDEBAR_COLOR = '#0f172a';
    public const DEFAULT_FONT_FAMILY = 'Inter';
    public const DEFAULT_THEME_MODE = 'light';

    protected $fillable = [
        'app_name',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'sidebar_color',
        'font_family',
        'theme_mode',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            self::clearSettingsCache();
        });

        static::deleted(function () {
            self::clearSettingsCache();
        });
    }

    /**
     * Limpia la caché persistente de configuración.
     */
    public static function clearSettingsCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Obtiene la instancia activa de configuración desde caché o BD.
     */
    public static function getSettings(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return self::first() ?? self::createDefaultInstance();
        });
    }

    /**
     * Crea o devuelve una instancia en memoria con valores de fábrica.
     */
    public static function createDefaultInstance(): self
    {
        return new self([
            'app_name' => self::DEFAULT_APP_NAME,
            'primary_color' => self::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => self::DEFAULT_SECONDARY_COLOR,
            'sidebar_color' => self::DEFAULT_SIDEBAR_COLOR,
            'font_family' => self::DEFAULT_FONT_FAMILY,
            'theme_mode' => self::DEFAULT_THEME_MODE,
            'logo_path' => null,
            'favicon_path' => null,
        ]);
    }

    /**
     * URL completa del logotipo.
     */
    public function getLogoUrlAttribute(): string
    {
        if (!empty($this->logo_path)) {
            if (Storage::disk('public')->exists($this->logo_path)) {
                return Storage::disk('public')->url($this->logo_path);
            }
            if (str_starts_with($this->logo_path, 'http') || str_starts_with($this->logo_path, '//')) {
                return $this->logo_path;
            }
            if (file_exists(public_path($this->logo_path))) {
                return asset($this->logo_path);
            }
        }

        if (file_exists(public_path('img/Logo.svg'))) {
            return asset('img/Logo.svg');
        }

        return asset('img/Logo.png');
    }

    /**
     * URL completa del favicon.
     */
    public function getFaviconUrlAttribute(): string
    {
        if (!empty($this->favicon_path)) {
            if (Storage::disk('public')->exists($this->favicon_path)) {
                return Storage::disk('public')->url($this->favicon_path);
            }
            if (str_starts_with($this->favicon_path, 'http') || str_starts_with($this->favicon_path, '//')) {
                return $this->favicon_path;
            }
            if (file_exists(public_path($this->favicon_path))) {
                return asset($this->favicon_path);
            }
        }

        return asset('favicon.ico');
    }
}

