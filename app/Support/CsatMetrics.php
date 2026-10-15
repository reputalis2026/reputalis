<?php

namespace App\Support;

use App\Models\Client;
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
     * Devuelve las métricas CSAT para el dashboard.
     * La caché de 5 min sirve para el resumen general. El panel del cliente pide datos en vivo.
     *
     * @param  string|array<int, string>|null  $clientScope  UUID cliente, lista de UUIDs, o null
     * @param  string  $period  today|7|30|all
     * @return array{avg_score: float|null, total: int, satisfied_pct: float|null, today_count: int}
     */
    public static function getMetrics(string|array|null $clientScope, string $period, bool $fresh = false): array
    {
        $user = auth()->user();

        $resolvedClientIds = self::resolveScopedClientIds($clientScope, $user);
        if (is_array($resolvedClientIds) && count($resolvedClientIds) === 0) {
            return [
                'avg_score' => null,
                'total' => 0,
                'satisfied_pct' => null,
                'today_count' => 0,
            ];
        }
        $scopeKey = $resolvedClientIds === null
            ? 'all'
            : implode(',', $resolvedClientIds);
        $cacheKey = 'csat_dashboard_v3_' . auth()->id() . '_' . $scopeKey . '_' . $period;

        if ($fresh) {
            return self::calculate($resolvedClientIds, $period);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($resolvedClientIds, $period) {
            return self::calculate($resolvedClientIds, $period);
        });
    }

    /**
     * @param  array<int, string>|null  $resolvedClientIds
     * @return array{avg_score: float|null, total: int, satisfied_pct: float|null, today_count: int}
     */
    private static function calculate(?array $resolvedClientIds, string $period): array
    {
        $baseQuery = CsatSurvey::query();
        if (is_array($resolvedClientIds)) {
            $baseQuery->whereIn('client_id', $resolvedClientIds);
        }

        $periodQuery = (clone $baseQuery)->when($period !== self::PERIOD_ALL, function ($q) use ($period) {
            [$from, $to] = self::dateRangeForPeriod($period);

            return $q->whereBetween('created_at', [$from, $to]);
        });

        $total = $periodQuery->count();
        $avgScore = $total > 0 ? (float) $periodQuery->avg('score') : null;
        $satisfiedCount = $total > 0 ? self::countSatisfiedSurveys(clone $periodQuery) : 0;
        $satisfiedPct = $total > 0 ? round(($satisfiedCount / $total) * 100, 1) : null;

        [$todayFrom, $todayTo] = self::dateRangeForPeriod(self::PERIOD_TODAY);
        $todayCount = (clone $baseQuery)
            ->whereBetween('created_at', [$todayFrom, $todayTo])
            ->count();

        return [
            'avg_score' => $avgScore,
            'total' => $total,
            'satisfied_pct' => $satisfiedPct,
            'today_count' => $todayCount,
        ];
    }

    private static function countSatisfiedSurveys(\Illuminate\Database\Eloquent\Builder $periodQuery): int
    {
        return (int) (clone $periodQuery)->whereIn('score', [4, 5])->count();
    }

    /**
     * @param  string|array<int, string>|null  $clientScope
     * @param  mixed  $user
     * @return array<int, string>|null
     */
    private static function resolveScopedClientIds(string|array|null $clientScope, mixed $user): ?array
    {
        $requestedIds = self::normalizeScopeIds($clientScope);

        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return $requestedIds;
        }

        if ($user->isClientOwner()) {
            $ownedId = $user->ownedClient?->id;
            if (! $ownedId) {
                return [];
            }

            return [$ownedId];
        }

        if ($user->isDistributor()) {
            $allowedIds = Client::query()
                ->where('created_by', $user->id)
                ->pluck('id')
                ->all();
            if (count($allowedIds) === 0) {
                return [];
            }

            if ($requestedIds === null) {
                sort($allowedIds);

                return $allowedIds;
            }

            $scoped = array_values(array_intersect($allowedIds, $requestedIds));
            sort($scoped);

            return $scoped;
        }

        return [];
    }

    /**
     * @param  string|array<int, string>|null  $clientScope
     * @return array<int, string>|null
     */
    private static function normalizeScopeIds(string|array|null $clientScope): ?array
    {
        if ($clientScope === null) {
            return null;
        }

        $values = is_array($clientScope) ? $clientScope : [$clientScope];
        $ids = [];
        foreach ($values as $value) {
            $id = trim((string) $value);
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    public static function pharmacyNow(): Carbon
    {
        return Carbon::now((string) config('elpulso.timezone', 'Europe/Madrid'));
    }

    /**
     * Hoy, 7 días (hoy y los 6 anteriores) o 30 días (hoy y los 29 anteriores),
     * en la hora de la farmacia y convertidos a la zona de la base de datos.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function boundsForPeriod(string $period): array
    {
        $now = self::pharmacyNow();
        $start = match ($period) {
            self::PERIOD_TODAY => $now->copy()->startOfDay(),
            self::PERIOD_30_DAYS => $now->copy()->subDays(29)->startOfDay(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };
        $tz = (string) config('app.timezone');

        return [
            $start->timezone($tz),
            $now->copy()->endOfDay()->timezone($tz),
        ];
    }

    /**
     * @return array{0: string, 1: string} [from datetime, to datetime]
     */
    public static function dateRangeForPeriod(string $period): array
    {
        [$from, $to] = self::boundsForPeriod($period);

        return [$from->toDateTimeString(), $to->toDateTimeString()];
    }
}
