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
     */
    public static function getScoring(User $user, ?float $saldoTotal = null): array
    {
        // 1. Si existe sobreescritura manual activa por el administrador, retornar score manual
        if ($user->override_score && !is_null($user->score_manual)) {
            $effectiveScore = (int) max(0, min(100, $user->score_manual));
            $isOverride = true;
        } else {
            // 2. Cálculo automático
            $score = 70; // Puntaje Base

            // +10 por cada comprobante aprobado
            $aprobados = Comprobante::where('user_id', $user->id)->where('status', 'aprobado')->count();
            $score += ($aprobados * 10);

            // -15 por cada comprobante rechazado
            $rechazados = Comprobante::where('user_id', $user->id)->where('status', 'rechazado')->count();
            $score -= ($rechazados * 15);

            // +5 por pedidos realizados en los últimos 30 días
            $recentOrders = Pedido::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $score += ($recentOrders * 5);

            // Penalización por deuda acumulada > $5,000
            if (is_null($saldoTotal)) {
                $totalVentas = Venta::where('user_id', $user->id)->sum('total_venta');
                $totalEntradas = Entrada::where('user_id', $user->id)->sum('precio_venta');
                $saldoTotal = max(0, $totalVentas - $totalEntradas);
            }

            if ($saldoTotal > 5000) {
                $score -= 20;
            }

            $effectiveScore = (int) max(0, min(100, $score));
            $isOverride = false;

            // Actualizar score_calculado en BD si cambió
            if ($user->score_calculado !== $effectiveScore) {
                User::where('id', $user->id)->update(['score_calculado' => $effectiveScore]);
                $user->score_calculado = $effectiveScore;
            }
        }

        return self::formatTier($effectiveScore, $isOverride, $user->notas_scoring);
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
