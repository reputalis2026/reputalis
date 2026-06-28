<?php

namespace App\Support\ClientDashboard;

use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use App\Models\CsatSurvey;
use App\Support\CsatMetrics;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class InternalReputationMetrics
{
    public const SURVEY_HISTORY_GROUPING_RANGE = 'range';

    public const SURVEY_HISTORY_GROUPING_DAYS = 'days';

    public const SURVEY_HISTORY_GROUPING_HOURS = 'hours';

    public static function normalizeSurveyHistoryGrouping(string $grouping): string
    {
        return in_array($grouping, [
            self::SURVEY_HISTORY_GROUPING_RANGE,
            self::SURVEY_HISTORY_GROUPING_DAYS,
            self::SURVEY_HISTORY_GROUPING_HOURS,
        ], true)
            ? $grouping
            : self::SURVEY_HISTORY_GROUPING_RANGE;
    }

    /**
     * @return array{avg_score: float|null, total: int, satisfied_pct: float|null, today_count: int}
     */
    public function getCsatMetrics(string $clientId, InternalReputationDateRange $range): array
    {
        $csatPeriod = $range->csatPeriod();

        if ($csatPeriod !== null) {
            return CsatMetrics::getMetrics($clientId, $csatPeriod);
        }

        $baseQuery = CsatSurvey::query()
            ->where('client_id', $clientId);

        $periodQuery = $this->applyDateRange(clone $baseQuery, $range);
        $total = $periodQuery->count();
        $avgScore = $total > 0 ? (float) $periodQuery->avg('score') : null;
        $satisfiedCount = $total > 0 ? $this->countSatisfiedSurveys(clone $periodQuery) : 0;
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
    }

    /**
     * @return array{surveys: int, rated_employees: int, avg_score: float|null}
     */
    public function getEmployeeSummary(string $clientId, InternalReputationDateRange $range): array
    {
        $query = $this->surveyQuery($clientId, $range)
            ->whereNotNull('csat_surveys.employee_id');

        $surveys = (clone $query)->count();

        return [
            'surveys' => $surveys,
            'rated_employees' => (clone $query)->distinct('csat_surveys.employee_id')->count('csat_surveys.employee_id'),
            'avg_score' => $surveys > 0 ? (float) (clone $query)->avg('csat_surveys.score') : null,
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, photo: string|null, is_active: bool, surveys: int, avg_score: float, score_counts: array<int, int>, score_percentages: array<int, float>}>
     */
    public function getEmployeeScoreRanking(string $clientId, InternalReputationDateRange $range): array
    {
        $rows = $this->surveyQuery($clientId, $range)
            ->join('employees', 'employees.id', '=', 'csat_surveys.employee_id')
            ->whereNotNull('csat_surveys.employee_id')
            ->where('employees.client_id', $clientId)
            ->select([
                'employees.id',
                'employees.name',
                'employees.photo',
                'employees.is_active',
                'csat_surveys.score',
                DB::raw('COUNT(*) as aggregate'),
            ])
            ->groupBy('employees.id', 'employees.name', 'employees.photo', 'employees.is_active', 'csat_surveys.score')
            ->get();

        return $rows
            ->groupBy('id')
            ->map(function ($employeeRows): array {
                $firstRow = $employeeRows->first();
                $scoreCounts = collect([1, 2, 3, 4, 5])
                    ->mapWithKeys(fn (int $score): array => [$score => 0])
                    ->all();

                foreach ($employeeRows as $row) {
                    $scoreCounts[(int) $row->score] = (int) $row->aggregate;
                }

                $surveys = array_sum($scoreCounts);
                $weightedScore = 0;
                foreach ($scoreCounts as $score => $count) {
                    $weightedScore += $score * $count;
                }

                return [
                    'id' => (string) $firstRow->id,
                    'name' => (string) $firstRow->name,
                    'photo' => $firstRow->photo ? (string) $firstRow->photo : null,
                    'is_active' => (bool) $firstRow->is_active,
                    'surveys' => $surveys,
                    'avg_score' => $surveys > 0 ? round($weightedScore / $surveys, 2) : 0.0,
                    'score_counts' => $scoreCounts,
                    'score_percentages' => collect($scoreCounts)
                        ->map(fn (int $count): float => $surveys > 0 ? round(($count / $surveys) * 100, 1) : 0.0)
                        ->all(),
                ];
            })
            ->sortBy([
                ['is_active', 'desc'],
                ['avg_score', 'desc'],
                ['surveys', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>, granularity: string, grouping: string, total: int}
     */
    public function getSurveyHistory(
        string $clientId,
        InternalReputationDateRange $range,
        string $grouping = self::SURVEY_HISTORY_GROUPING_RANGE,
    ): array {
        return match (self::normalizeSurveyHistoryGrouping($grouping)) {
            self::SURVEY_HISTORY_GROUPING_DAYS => $this->getSurveyHistoryByWeekday($clientId, $range),
            self::SURVEY_HISTORY_GROUPING_HOURS => $this->getSurveyHistoryByHourOfDay($clientId, $range),
            default => $this->getSurveyHistoryByRange($clientId, $range),
        };
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>, granularity: string, grouping: string, total: int}
     */
    private function getSurveyHistoryByRange(string $clientId, InternalReputationDateRange $range): array
    {
        [$from, $until] = $this->resolveHistoryBounds($clientId, $range);
        $granularity = $this->resolveHistoryGranularity($range, $from, $until);

        $rows = $this->applyDateRange(
            CsatSurvey::query()->where('csat_surveys.client_id', $clientId),
            new InternalReputationDateRange(
                InternalReputationDateRange::TYPE_CUSTOM,
                $from?->toDateString(),
                $until?->toDateString(),
            ),
            'csat_surveys.created_at',
        )
            ->selectRaw("date_trunc('{$granularity}', csat_surveys.created_at) as bucket")
            ->selectRaw('COUNT(*) as aggregate')
            ->groupByRaw("date_trunc('{$granularity}', csat_surveys.created_at)")
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                Carbon::parse($row->bucket)->format('Y-m-d H:i:s') => (int) $row->aggregate,
            ]);

        $labels = [];
        $counts = [];
        foreach ($this->historyPeriod($from, $until, $granularity) as $bucket) {
            $key = $bucket->copy()->startOf($granularity)->format('Y-m-d H:i:s');
            $labels[] = $this->formatHistoryLabel($bucket, $granularity);
            $counts[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'granularity' => $granularity,
            'grouping' => self::SURVEY_HISTORY_GROUPING_RANGE,
            'total' => array_sum($counts),
        ];
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>, granularity: string, grouping: string, total: int}
     */
    private function getSurveyHistoryByWeekday(string $clientId, InternalReputationDateRange $range): array
    {
        $rows = $this->surveyQuery($clientId, $range)
            ->selectRaw('EXTRACT(ISODOW FROM csat_surveys.created_at)::int as bucket')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupByRaw('EXTRACT(ISODOW FROM csat_surveys.created_at)')
            ->pluck('aggregate', 'bucket');

        $labels = [];
        $counts = [];
        $locale = app()->getLocale();

        for ($day = 1; $day <= 7; $day++) {
            $labels[] = Carbon::now()
                ->locale($locale)
                ->startOfWeek(Carbon::MONDAY)
                ->addDays($day - 1)
                ->isoFormat('dddd');
            $counts[] = (int) ($rows[$day] ?? $rows[(string) $day] ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'granularity' => 'weekday',
            'grouping' => self::SURVEY_HISTORY_GROUPING_DAYS,
            'total' => array_sum($counts),
        ];
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>, granularity: string, grouping: string, total: int}
     */
    private function getSurveyHistoryByHourOfDay(string $clientId, InternalReputationDateRange $range): array
    {
        $rows = $this->surveyQuery($clientId, $range)
            ->selectRaw('EXTRACT(HOUR FROM csat_surveys.created_at)::int as bucket')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupByRaw('EXTRACT(HOUR FROM csat_surveys.created_at)')
            ->pluck('aggregate', 'bucket');

        $labels = [];
        $counts = [];

        for ($hour = 0; $hour <= 23; $hour++) {
            $labels[] = (string) $hour;
            $counts[] = (int) ($rows[$hour] ?? $rows[(string) $hour] ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'granularity' => 'hour_of_day',
            'grouping' => self::SURVEY_HISTORY_GROUPING_HOURS,
            'total' => array_sum($counts),
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     averages: array<int, float|null>,
     *     counts: array<int, int>,
     *     rows: array<int, array{label: string, count: int, average: float|null}>,
     *     granularity: string
     * }
     */
    public function getScoreTrend(string $clientId, InternalReputationDateRange $range): array
    {
        [$from, $until] = $this->resolveHistoryBounds($clientId, $range);
        $granularity = $this->resolveHistoryGranularity($range, $from, $until);

        $rows = $this->applyDateRange(
            CsatSurvey::query()->where('csat_surveys.client_id', $clientId),
            new InternalReputationDateRange(
                InternalReputationDateRange::TYPE_CUSTOM,
                $from?->toDateString(),
                $until?->toDateString(),
            ),
            'csat_surveys.created_at',
        )
            ->selectRaw("date_trunc('{$granularity}', csat_surveys.created_at) as bucket")
            ->selectRaw('AVG(csat_surveys.score) as average_score')
            ->selectRaw('COUNT(*) as survey_count')
            ->groupByRaw("date_trunc('{$granularity}', csat_surveys.created_at)")
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                Carbon::parse($row->bucket)->format('Y-m-d H:i:s') => [
                    'average' => round((float) $row->average_score, 2),
                    'count' => (int) $row->survey_count,
                ],
            ]);

        $labels = [];
        $averages = [];
        $counts = [];
        $tableRows = [];

        foreach ($this->historyPeriod($from, $until, $granularity) as $bucket) {
            $key = $bucket->copy()->startOf($granularity)->format('Y-m-d H:i:s');
            $label = $this->formatHistoryLabel($bucket, $granularity);
            $average = $rows[$key]['average'] ?? null;
            $count = (int) ($rows[$key]['count'] ?? 0);

            $labels[] = $label;
            $averages[] = $average;
            $counts[] = $count;

            if ($count > 0) {
                $tableRows[] = [
                    'label' => $label,
                    'count' => $count,
                    'average' => $average,
                ];
            }
        }

        return [
            'labels' => $labels,
            'averages' => $averages,
            'counts' => $counts,
            'rows' => $tableRows,
            'granularity' => $granularity,
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     averages: array<int, float|null>,
     *     counts: array<int, int>,
     *     granularity: string
     * }
     */
    public function getEmployeeScoreTrend(
        string $clientId,
        string $employeeId,
        InternalReputationDateRange $range,
    ): array {
        [$from, $until] = $this->resolveHistoryBounds($clientId, $range);
        $granularity = $this->resolveHistoryGranularity($range, $from, $until);

        $rows = $this->surveyQuery($clientId, $range)
            ->where('csat_surveys.employee_id', $employeeId)
            ->whereNotNull('csat_surveys.employee_id')
            ->selectRaw("date_trunc('{$granularity}', csat_surveys.created_at) as bucket")
            ->selectRaw('AVG(csat_surveys.score) as average_score')
            ->selectRaw('COUNT(*) as survey_count')
            ->groupByRaw("date_trunc('{$granularity}', csat_surveys.created_at)")
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                Carbon::parse($row->bucket)->format('Y-m-d H:i:s') => [
                    'average' => round((float) $row->average_score, 2),
                    'count' => (int) $row->survey_count,
                ],
            ]);

        $labels = [];
        $averages = [];
        $counts = [];

        foreach ($this->historyPeriod($from, $until, $granularity) as $bucket) {
            $key = $bucket->copy()->startOf($granularity)->format('Y-m-d H:i:s');
            $labels[] = $this->formatHistoryLabel($bucket, $granularity);
            $averages[] = $rows[$key]['average'] ?? null;
            $counts[] = (int) ($rows[$key]['count'] ?? 0);
        }

        return [
            'labels' => $labels,
            'averages' => $averages,
            'counts' => $counts,
            'granularity' => $granularity,
        ];
    }

    /**
     * @return array{satisfied_pct: float|null, satisfied_count: int, total: int}
     */
    public function getEmployeeSatisfiedMetrics(
        string $clientId,
        string $employeeId,
        InternalReputationDateRange $range,
    ): array {
        $query = $this->surveyQuery($clientId, $range)
            ->where('csat_surveys.employee_id', $employeeId)
            ->whereNotNull('csat_surveys.employee_id');

        $total = (clone $query)->count();
        $satisfiedCount = $total > 0 ? $this->countSatisfiedSurveys(clone $query) : 0;

        return [
            'satisfied_pct' => $total > 0 ? round(($satisfiedCount / $total) * 100, 1) : null,
            'satisfied_count' => $satisfiedCount,
            'total' => $total,
        ];
    }

    /**
     * @return array<int, array{label: string, is_active: bool, count: int, percentage: float}>
     */
    public function getEmployeeImprovementPoints(
        string $clientId,
        string $employeeId,
        InternalReputationDateRange $range,
        ?string $locale = null,
    ): array {
        $employeeQuery = $this->surveyQuery($clientId, $range)
            ->where('csat_surveys.employee_id', $employeeId)
            ->whereNotNull('csat_surveys.employee_id');

        $totalSurveys = (clone $employeeQuery)->count();

        if ($totalSurveys === 0) {
            return [];
        }

        $rows = (clone $employeeQuery)
            ->whereNotNull('csat_surveys.improvement_option_id')
            ->select('csat_surveys.improvement_option_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('csat_surveys.improvement_option_id')
            ->orderByDesc('aggregate')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $options = ClientImprovementOption::query()
            ->whereIn('id', $rows->pluck('improvement_option_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($row) use ($options, $totalSurveys, $locale): array {
                $option = $options->get($row->improvement_option_id);
                $label = $option?->labelForLocale($locale ?? app()->getLocale())
                    ?: $this->deletedImprovementOptionLabel($locale);

                $count = (int) $row->aggregate;

                return [
                    'label' => $label,
                    'is_active' => (bool) ($option?->is_active ?? false),
                    'count' => $count,
                    'percentage' => round(($count / $totalSurveys) * 100, 1),
                ];
            })
            ->sortBy([
                ['is_active', 'desc'],
                ['percentage', 'desc'],
                ['count', 'desc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{mentions: int, detected_points: int, top_point_label: string|null}
     */
    public function getImprovementSummary(string $clientId, InternalReputationDateRange $range): array
    {
        $query = $this->surveyQuery($clientId, $range)
            ->whereNotNull('improvement_option_id');

        $mentions = (clone $query)->count();
        $topPoint = (clone $query)
            ->select('improvement_option_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('improvement_option_id')
            ->orderByDesc('aggregate')
            ->first();

        $topPointLabel = null;
        if ($topPoint?->improvement_option_id) {
            $option = ClientImprovementOption::find($topPoint->improvement_option_id);
            $topPointLabel = $option?->labelForLocale(app()->getLocale());
        }

        return [
            'mentions' => $mentions,
            'detected_points' => (clone $query)->distinct('improvement_option_id')->count('improvement_option_id'),
            'top_point_label' => filled($topPointLabel) ? $topPointLabel : null,
        ];
    }

    /**
     * @return array{question: string, total_surveys: int, options: array<int, array{id: string, label: string, is_active: bool, count: int, percentage: float}>}
     */
    public function getImprovementOptionRanking(string $clientId, InternalReputationDateRange $range, ?string $locale = null): array
    {
        $config = ClientImprovementConfig::query()
            ->where('client_id', $clientId)
            ->with('options')
            ->first();

        $totalSurveys = $this->surveyQuery($clientId, $range)->count();
        $counts = $this->surveyQuery($clientId, $range)
            ->whereNotNull('csat_surveys.improvement_option_id')
            ->select('csat_surveys.improvement_option_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('csat_surveys.improvement_option_id')
            ->pluck('aggregate', 'improvement_option_id');

        $activeOptionModels = $config?->activeOptions()->get() ?? collect();
        $allOptionModels = $config?->options()->get()->keyBy('id') ?? collect();
        $activeOptionIds = $activeOptionModels->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $activeOptions = $activeOptionModels
            ->map(fn (ClientImprovementOption $option): array => [
                'id' => (string) $option->id,
                'label' => $option->labelForLocale($locale),
                'is_active' => true,
                'count' => (int) ($counts[$option->id] ?? 0),
                'percentage' => $totalSurveys > 0
                    ? round(((int) ($counts[$option->id] ?? 0) / $totalSurveys) * 100, 1)
                    : 0.0,
            ]);

        $deletedOptions = collect($counts)
            ->reject(fn ($count, $optionId): bool => in_array((string) $optionId, $activeOptionIds, true))
            ->filter(fn ($count): bool => (int) $count > 0)
            ->map(function ($count, $optionId) use ($allOptionModels, $locale, $totalSurveys): array {
                $option = $allOptionModels->get((string) $optionId);

                return [
                    'id' => (string) $optionId,
                    'label' => $option?->labelForLocale($locale)
                        ?: $this->deletedImprovementOptionLabel($locale),
                    'is_active' => false,
                    'count' => (int) $count,
                    'percentage' => $totalSurveys > 0
                        ? round(((int) $count / $totalSurveys) * 100, 1)
                        : 0.0,
                ];
            })
            ->values();

        $options = $activeOptions
            ->concat($deletedOptions)
            ->sortBy([
                ['is_active', 'desc'],
                ['percentage', 'desc'],
                ['count', 'desc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'question' => $config?->titleForLocale($locale)
                ?? ClientImprovementConfig::defaultTitles()[ClientImprovementConfig::DEFAULT_LOCALE],
            'total_surveys' => $totalSurveys,
            'options' => $options,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     period_label: string,
     *     labels: array<int, string>,
     *     percentages: array<int, float|null>,
     *     counts: array<int, int>,
     *     totals: array<int, int>,
     *     rows: array<int, array{label: string, count: int, total: int, percentage: float}>,
     *     granularity: string,
     *     is_active: bool
     * }|null
     */
    public function getImprovementOptionTrend(
        string $clientId,
        string $optionId,
        InternalReputationDateRange $range,
        ?string $locale = null,
    ): ?array {
        $option = ClientImprovementOption::query()
            ->where('id', $optionId)
            ->whereHas(
                'clientImprovementConfig',
                fn (Builder $query) => $query->where('client_id', $clientId),
            )
            ->first();

        [$from, $until] = $this->resolveHistoryBounds($clientId, $range);
        $granularity = $this->resolveHistoryGranularity($range, $from, $until);

        if (! $option) {
            $hasHistoricalSurveys = $this->applyDateRange(
                CsatSurvey::query()->where('csat_surveys.client_id', $clientId),
                new InternalReputationDateRange(
                    InternalReputationDateRange::TYPE_CUSTOM,
                    $from?->toDateString(),
                    $until?->toDateString(),
                ),
                'csat_surveys.created_at',
            )
                ->where('csat_surveys.improvement_option_id', $optionId)
                ->exists();

            if (! $hasHistoricalSurveys) {
                return null;
            }
        }

        $rows = $this->applyDateRange(
            CsatSurvey::query()->where('csat_surveys.client_id', $clientId),
            new InternalReputationDateRange(
                InternalReputationDateRange::TYPE_CUSTOM,
                $from?->toDateString(),
                $until?->toDateString(),
            ),
            'csat_surveys.created_at',
        )
            ->selectRaw("date_trunc('{$granularity}', csat_surveys.created_at) as bucket")
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw(
                'SUM(CASE WHEN csat_surveys.improvement_option_id = ? THEN 1 ELSE 0 END) as option_count',
                [$optionId],
            )
            ->groupByRaw("date_trunc('{$granularity}', csat_surveys.created_at)")
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                Carbon::parse($row->bucket)->format('Y-m-d H:i:s') => [
                    'count' => (int) $row->option_count,
                    'total' => (int) $row->total_count,
                ],
            ]);

        $labels = [];
        $percentages = [];
        $counts = [];
        $totals = [];
        $tableRows = [];

        foreach ($this->historyPeriod($from, $until, $granularity) as $bucket) {
            $key = $bucket->copy()->startOf($granularity)->format('Y-m-d H:i:s');
            $label = $this->formatHistoryLabel($bucket, $granularity);
            $count = (int) ($rows[$key]['count'] ?? 0);
            $total = (int) ($rows[$key]['total'] ?? 0);
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : null;

            $labels[] = $label;
            $percentages[] = $percentage;
            $counts[] = $count;
            $totals[] = $total;

            if ($total > 0) {
                $tableRows[] = [
                    'label' => $label,
                    'count' => $count,
                    'total' => $total,
                    'percentage' => $percentage ?? 0.0,
                ];
            }
        }

        return [
            'id' => $option ? (string) $option->id : $optionId,
            'label' => $option?->labelForLocale($locale ?? app()->getLocale())
                ?: $this->deletedImprovementOptionLabel($locale),
            'is_active' => (bool) ($option?->is_active ?? false),
            'period_label' => $this->formatRangeLabel($from, $until),
            'labels' => $labels,
            'percentages' => $percentages,
            'counts' => $counts,
            'totals' => $totals,
            'rows' => $tableRows,
            'granularity' => $granularity,
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, photo: string|null, is_active: bool, count: int, percentage: float}>
     */
    public function getImprovementOptionEmployeeRanking(
        string $clientId,
        string $optionId,
        InternalReputationDateRange $range,
    ): array {
        $rows = $this->surveyQuery($clientId, $range)
            ->join('employees', 'employees.id', '=', 'csat_surveys.employee_id')
            ->where('employees.client_id', $clientId)
            ->where('csat_surveys.improvement_option_id', $optionId)
            ->whereNotNull('csat_surveys.employee_id')
            ->select([
                'employees.id',
                'employees.name',
                'employees.photo',
                'employees.is_active',
                DB::raw('COUNT(*) as aggregate'),
            ])
            ->groupBy('employees.id', 'employees.name', 'employees.photo', 'employees.is_active')
            ->orderByDesc('employees.is_active')
            ->orderByDesc('aggregate')
            ->orderBy('employees.name')
            ->get();

        $total = (int) $rows->sum('aggregate');

        return $rows
            ->map(fn ($row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'photo' => $row->photo ? (string) $row->photo : null,
                'is_active' => (bool) $row->is_active,
                'count' => (int) $row->aggregate,
                'percentage' => $total > 0 ? round(((int) $row->aggregate / $total) * 100, 1) : 0.0,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{score: int, count: int, percentage: float}>
     */
    public function getScoreBreakdown(string $clientId, InternalReputationDateRange $range): array
    {
        $counts = $this->surveyQuery($clientId, $range)
            ->select('csat_surveys.score', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('csat_surveys.score')
            ->pluck('aggregate', 'score');

        $total = (int) $counts->sum();

        return collect([5, 4, 3, 2, 1])
            ->map(fn (int $score): array => [
                'score' => $score,
                'count' => (int) ($counts[$score] ?? 0),
                'percentage' => $total > 0
                    ? round(((int) ($counts[$score] ?? 0) / $total) * 100, 1)
                    : 0.0,
            ])
            ->all();
    }

    public function surveyQuery(string $clientId, InternalReputationDateRange $range): Builder
    {
        return $this->applyDateRange(
            CsatSurvey::query()->where('csat_surveys.client_id', $clientId),
            $range,
            'csat_surveys.created_at',
        );
    }

    public function applyDateRange(Builder $query, InternalReputationDateRange $range, string $column = 'created_at'): Builder
    {
        [$from, $until] = $range->bounds();

        return $query
            ->when($from, fn (Builder $q) => $q->where($column, '>=', $from))
            ->when($until, fn (Builder $q) => $q->where($column, '<=', $until));
    }

    private function countSatisfiedSurveys(Builder $periodQuery): int
    {
        return (int) (clone $periodQuery)->whereIn('score', [4, 5])->count();
    }

    private function deletedImprovementOptionLabel(?string $locale = null): string
    {
        return Lang::get(
            'client.dashboard.improvement_ranking.deleted_option',
            [],
            $locale ?? app()->getLocale(),
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveHistoryBounds(string $clientId, InternalReputationDateRange $range): array
    {
        [$from, $until] = $range->bounds();

        if (! $from) {
            $firstSurveyAt = CsatSurvey::query()
                ->where('client_id', $clientId)
                ->min('created_at');

            $from = $firstSurveyAt ? Carbon::parse($firstSurveyAt)->startOfDay() : now()->startOfMonth();
        }

        $until ??= now()->endOfDay();

        if ($from->greaterThan($until)) {
            return [$until->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $until];
    }

    private function resolveHistoryGranularity(InternalReputationDateRange $range, Carbon $from, Carbon $until): string
    {
        if ($range->rangeType === InternalReputationDateRange::TYPE_TODAY) {
            return 'hour';
        }

        return $from->diffInDays($until) <= 45 ? 'day' : 'month';
    }

    /**
     * @return iterable<int, Carbon>
     */
    private function historyPeriod(Carbon $from, Carbon $until, string $granularity): iterable
    {
        $start = $from->copy()->startOf($granularity);
        $end = $until->copy()->startOf($granularity);

        return CarbonPeriod::create($start, "1 {$granularity}", $end);
    }

    private function formatHistoryLabel(Carbon $bucket, string $granularity): string
    {
        return match ($granularity) {
            'hour' => $bucket->format('H:00'),
            'day' => $bucket->format('d/m'),
            default => $bucket->isoFormat('MMM YY'),
        };
    }

    private function formatRangeLabel(Carbon $from, Carbon $until): string
    {
        return $from->format('d/m/Y') . ' - ' . $until->format('d/m/Y');
    }
}
