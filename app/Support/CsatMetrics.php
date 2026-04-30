<?php

namespace App\Support;

use App\Models\Client;
use App\Models\CsatSurvey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
     * @param  string|array<int, string>|null  $clientScope  UUID cliente, lista de UUIDs, o null
     * @param  string  $period  today|7|30|all
     * @return array{avg_score: float|null, total: int, satisfied_pct: float|null, today_count: int}
     */
    public static function getMetrics(string|array|null $clientScope, string $period): array
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

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($resolvedClientIds, $period) {
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

    private static function countSatisfiedSurveys(\Illuminate\Database\Eloquent\Builder $periodQuery): int
    {
        $counts = $periodQuery
            ->select('score', 'positive_scores_used', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('score', 'positive_scores_used')
            ->get();

        if ($counts->isEmpty()) {
            return 0;
        }

        return (int) $counts->sum(function ($row): int {
            $positiveScores = is_array($row->positive_scores_used)
                ? $row->positive_scores_used
                : (json_decode((string) $row->positive_scores_used, true) ?: [4, 5]);

            return in_array((int) $row->score, $positiveScores, true)
                ? (int) $row->aggregate
                : 0;
        });
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
