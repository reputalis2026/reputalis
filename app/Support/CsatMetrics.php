<?php

namespace App\Support;

use App\Models\CsatSurvey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CsatMetrics
{
    public const PERIOD_TODAY = 'today';
    public const PERIOD_7_DAYS = '7';
    public const PERIOD_30_DAYS = '30';
    public const PERIOD_ALL = 'all';

    public const CACHE_TTL_SECONDS = 300; // 5 minutes

    /**
     * Devuelve las métricas CSAT para el dashboard (con cache de 5 min).
     *
     * @param  string|null  $clientId  null = todos los clientes (solo superadmin)
     * @param  string  $period  today|7|30|all
     * @return array{avg_score: float|null, total: int, satisfied_pct: float|null, today_count: int}
     */
    public static function getMetrics(?string $clientId, string $period): array
    {
        $clientId = filled($clientId) ? trim((string) $clientId) : null;
        $user = auth()->user();
        // Propietarios de cliente solo ven su cliente; si no tienen cliente, métricas vacías (nunca global)
        if ($user && ! $user->isSuperAdmin() && $clientId === null) {
            return [
                'avg_score' => null,
                'total' => 0,
                'satisfied_pct' => null,
                'today_count' => 0,
            ];
        }
        $cacheKey = 'csat_dashboard_' . auth()->id() . '_' . ($clientId ?? 'all') . '_' . $period;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($clientId, $period) {
            $baseQuery = CsatSurvey::query();
            if ($clientId !== null && $clientId !== '') {
                $baseQuery->where('client_id', $clientId);
            }

            $periodQuery = (clone $baseQuery)->when($period !== self::PERIOD_ALL, function ($q) use ($period) {
                [$from, $to] = self::dateRangeForPeriod($period);
                return $q->whereBetween('created_at', [$from, $to]);
            });

            $total = $periodQuery->count();
            $avgScore = $total > 0 ? (float) $periodQuery->avg('score') : null;
            $satisfiedCount = $total > 0
                ? (clone $periodQuery)->whereIn('score', [4, 5])->count()
                : 0;
            $satisfiedPct = $total > 0 ? round(($satisfiedCount / $total) * 100, 1) : null;

            $todayCount = (clone $baseQuery)
                ->whereDate('created_at', Carbon::today())
                ->count();

            return [
                'avg_score' => $avgScore,
                'total' => $total,
                'satisfied_pct' => $satisfiedPct,
                'today_count' => $todayCount,
            ];
        });
    }

    /**
     * @return array{0: string, 1: string} [from datetime, to datetime]
     */
    public static function dateRangeForPeriod(string $period): array
    {
        return match ($period) {
            self::PERIOD_TODAY => [
                Carbon::today()->startOfDay()->toDateTimeString(),
                Carbon::today()->endOfDay()->toDateTimeString(),
            ],
            self::PERIOD_7_DAYS => [
                Carbon::today()->subDays(7)->startOfDay()->toDateTimeString(),
                Carbon::today()->endOfDay()->toDateTimeString(),
            ],
            self::PERIOD_30_DAYS => [
                Carbon::today()->subDays(30)->startOfDay()->toDateTimeString(),
                Carbon::today()->endOfDay()->toDateTimeString(),
            ],
            default => [
                Carbon::today()->subDays(7)->startOfDay()->toDateTimeString(),
                Carbon::today()->endOfDay()->toDateTimeString(),
            ],
        };
    }
}
