<?php

namespace App\Services;

use App\Models\User;
use App\Models\Comprobante;
use App\Models\Pedido;
use App\Models\Entrada;
use App\Models\Venta;

class ClientScoringService
{
    /**
     * Calcula o resuelve el scoring de crédito del cliente.
     * Uso general (hace queries a DB). Para el dashboard usar getScoringFromData().
     */
    public static function getScoring(User $user, ?float $saldoTotal = null): array
    {
        // 1. Si existe sobreescritura manual activa por el administrador, retornar score manual
        if ($user->override_score && !is_null($user->score_manual)) {
            $effectiveScore = (int) max(0, min(100, $user->score_manual));
            $isOverride = true;
        } else {
            // 2. Cálculo automático con cache de 15 minutos
            $saldoKey = is_null($saldoTotal) ? 'x' : (string)(int)($saldoTotal);
            $cacheKey  = "scoring_{$user->id}_{$saldoKey}";

            return \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function () use ($user, $saldoTotal) {
                $score = 70; // Puntaje Base

                $aprobados = Comprobante::where('user_id', $user->id)->where('status', 'aprobado')->count();
                $score += ($aprobados * 10);

                $rechazados = Comprobante::where('user_id', $user->id)->where('status', 'rechazado')->count();
                $score -= ($rechazados * 15);

                $recentOrders = Pedido::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count();
                $score += ($recentOrders * 5);

                if (is_null($saldoTotal)) {
                    $totalVentas  = Venta::where('user_id', $user->id)->sum('total_venta');
                    $totalEntradas = Entrada::where('user_id', $user->id)->sum('precio_venta');
                    $saldoTotal = max(0, $totalVentas - $totalEntradas);
                }

                if ($saldoTotal > 5000) {
                    $score -= 20;
                }

                $effectiveScore = (int) max(0, min(100, $score));

                if ($user->score_calculado !== $effectiveScore) {
                    User::where('id', $user->id)->update(['score_calculado' => $effectiveScore]);
                    $user->score_calculado = $effectiveScore;
                }

                return self::formatTier($effectiveScore, false, $user->notas_scoring);
            });
        }

        return self::formatTier($effectiveScore, $isOverride, $user->notas_scoring);
    }

    /**
     * Versión de alto rendimiento para el dashboard.
     * Recibe los conteos ya precargados en bulk (sin queries adicionales por usuario).
     */
    public static function getScoringFromData(
        User $user,
        int $aprobados,
        int $rechazados,
        int $recentOrders,
        float $saldoTotal
    ): array {
        if ($user->override_score && !is_null($user->score_manual)) {
            return self::formatTier((int) max(0, min(100, $user->score_manual)), true, $user->notas_scoring);
        }

        $score = 70;
        $score += ($aprobados * 10);
        $score -= ($rechazados * 15);
        $score += ($recentOrders * 5);

        if ($saldoTotal > 5000) {
            $score -= 20;
        }

        $effectiveScore = (int) max(0, min(100, $score));

        // Actualizar en BD solo si el score cambió (sin bloquear el render)
        if ($user->score_calculado !== $effectiveScore) {
            User::where('id', $user->id)->update(['score_calculado' => $effectiveScore]);
        }

        return self::formatTier($effectiveScore, false, $user->notas_scoring);
    }

    /**
     * Da formato a la categoría, insignia visual y metadatos del score.
     */
    public static function formatTier(int $score, bool $isOverride = false, ?string $notas = null): array
    {
        if ($score >= 80) {
            $tier = 'platino';
            $label = '🌟 Platino VIP';
            $badgeBg = 'bg-emerald-100 text-emerald-800 border border-emerald-300';
            $colorHex = '#10b981';
        } elseif ($score >= 50) {
            $tier = 'regular';
            $label = '🟢 Regular';
            $badgeBg = 'bg-indigo-100 text-indigo-800 border border-indigo-300';
            $colorHex = '#6366f1';
        } else {
            $tier = 'riesgo';
            $label = '⚠️ En Riesgo';
            $badgeBg = 'bg-red-100 text-red-800 border border-red-300 animate-pulse';
            $colorHex = '#ef4444';
        }

        return [
            'score' => $score,
            'tier' => $tier,
            'label' => $label,
            'badge_bg' => $badgeBg,
            'color_hex' => $colorHex,
            'is_override' => $isOverride,
            'notas' => $notas,
        ];
    }
}
