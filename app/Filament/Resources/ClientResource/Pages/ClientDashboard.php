<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\Concerns\HasClientPageTitle;
use App\Models\Client;
use App\Support\ClientDashboard\InternalReputationDateRange;
use App\Support\ClientDashboard\InternalReputationMetrics;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Storage;

class ClientDashboard extends Page
{
    use HasClientPageTitle;
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.client-dashboard';

    public string $activeReputationTab = 'internal';

    public string $range_type = InternalReputationDateRange::TYPE_ALL;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public string $survey_history_grouping = InternalReputationMetrics::SURVEY_HISTORY_GROUPING_RANGE;

    public bool $showScoreTrendDetail = false;

    public ?string $selectedEmployeeId = null;

    public bool $showEmployeeDetail = false;

    public static function getNavigationLabel(): string
    {
        return __('client.menu.dashboard');
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(ClientResource::canView($this->getClientRecord()), 403);
    }

    public function switchReputationTab(string $tab): void
    {
        $this->activeReputationTab = in_array($tab, ['internal', 'external'], true)
            ? $tab
            : 'internal';
    }

    public function setRangeType(string $rangeType): void
    {
        $this->resetEmployeeDetail();
        $this->range_type = InternalReputationDateRange::normalizeRangeType($rangeType);

        if ($this->range_type === InternalReputationDateRange::TYPE_CUSTOM) {
            $this->date_from = $this->getDefaultCustomDateFrom();
            $this->date_to = now()->toDateString();

            return;
        }

        if ($this->range_type !== InternalReputationDateRange::TYPE_CUSTOM) {
            $this->date_from = null;
            $this->date_to = null;
        }
    }

    public function updated(string $property, mixed $value = null): void
    {
        if (in_array($property, ['date_from', 'date_to'], true)) {
            $this->resetEmployeeDetail();
            $this->range_type = InternalReputationDateRange::TYPE_CUSTOM;
        }
    }

    public function clearCustomRange(): void
    {
        $this->resetEmployeeDetail();
        $this->date_from = null;
        $this->date_to = null;
        $this->range_type = InternalReputationDateRange::TYPE_ALL;
    }

    public function setSurveyHistoryGrouping(string $grouping): void
    {
        $this->survey_history_grouping = InternalReputationMetrics::normalizeSurveyHistoryGrouping($grouping);
    }

    public function openScoreTrendDetail(): void
    {
        $this->showScoreTrendDetail = true;
    }

    public function closeScoreTrendDetail(): void
    {
        $this->showScoreTrendDetail = false;
    }

    public function openEmployeeDetail(string $employeeId): void
    {
        $isListed = collect($this->getEmployeeRanking())->contains(
            fn (array $employee): bool => $employee['id'] === $employeeId,
        );

        if (! $isListed) {
            return;
        }

        $this->selectedEmployeeId = $employeeId;
        $this->showEmployeeDetail = true;
    }

    public function closeEmployeeDetail(): void
    {
        $this->resetEmployeeDetail();
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     photo_url: string|null,
     *     initials: string,
     *     surveys: int,
     *     avg_score: string,
     *     avg_score_raw: float,
     *     gauge_percent: float,
     *     gauge_color: string,
     *     period_label: string,
     *     satisfied_pct: string,
     *     satisfied_pct_raw: float|null,
     *     satisfied_color: string,
     *     satisfied_chart_config: array{satisfiedPercent: float, satisfiedColor: string, satisfiedValue: string, trackColor: string},
     *     rating_groups: array<int, array{label: string, count: int, percentage: float, color: string}>,
     *     improvement_points: array<int, array{label: string, count: int, percentage: float}>,
     *     trend_chart_config: array{labels: array<int, string>, values: array<int, float|null>, seriesLabel: string, emptyLabel: string}
     * }|null
     */
    public function getSelectedEmployeeDetail(): ?array
    {
        if (! $this->showEmployeeDetail || ! $this->selectedEmployeeId) {
            return null;
        }

        $employee = collect(app(InternalReputationMetrics::class)->getEmployeeScoreRanking(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
        ))->firstWhere('id', $this->selectedEmployeeId);

        if (! $employee) {
            return null;
        }

        $avgScoreRaw = (float) $employee['avg_score'];
        $scoreColors = [
            1 => '#FF3901',
            2 => '#FF9880',
            3 => '#FFC60F',
            4 => '#8DFFA8',
            5 => '#01FF01',
        ];
        $trend = app(InternalReputationMetrics::class)->getEmployeeScoreTrend(
            $this->getClientRecord()->id,
            $this->selectedEmployeeId,
            $this->getInternalReputationDateRange(),
        );
        $satisfiedMetrics = app(InternalReputationMetrics::class)->getEmployeeSatisfiedMetrics(
            $this->getClientRecord()->id,
            $this->selectedEmployeeId,
            $this->getInternalReputationDateRange(),
        );
        $improvementPoints = app(InternalReputationMetrics::class)->getEmployeeImprovementPoints(
            $this->getClientRecord()->id,
            $this->selectedEmployeeId,
            $this->getInternalReputationDateRange(),
            app()->getLocale(),
        );
        $rangeContext = $this->getRangeContextSummary();
        $satisfiedPctRaw = $satisfiedMetrics['satisfied_pct'] !== null
            ? (float) $satisfiedMetrics['satisfied_pct']
            : null;

        return [
            'id' => (string) $employee['id'],
            'name' => (string) $employee['name'],
            'photo_url' => $employee['photo'] ? Storage::disk('public')->url($employee['photo']) : null,
            'initials' => $this->getEmployeeInitials((string) $employee['name']),
            'surveys' => (int) $employee['surveys'],
            'avg_score' => number_format($avgScoreRaw, 2, ',', ' '),
            'avg_score_raw' => $avgScoreRaw,
            'gauge_percent' => round(($avgScoreRaw / 5) * 100, 1),
            'gauge_color' => match (true) {
                $avgScoreRaw >= 4 => '#22c55e',
                $avgScoreRaw >= 3 => '#f59e0b',
                default => '#ef4444',
            },
            'period_label' => __('client.dashboard.employee_ranking.detail_period', [
                'from' => $rangeContext['date_from'],
                'to' => $rangeContext['date_to'],
            ]),
            'satisfied_pct' => $this->formatSatisfiedPercent($satisfiedPctRaw),
            'satisfied_pct_raw' => $satisfiedPctRaw,
            'satisfied_color' => $satisfiedPctRaw !== null && $satisfiedPctRaw > 50 ? '#22c55e' : '#fb7185',
            'satisfied_chart_config' => [
                'satisfiedPercent' => (float) ($satisfiedPctRaw ?? 0),
                'satisfiedColor' => $satisfiedPctRaw !== null && $satisfiedPctRaw > 50 ? '#22c55e' : '#fb7185',
                'satisfiedValue' => $this->formatSatisfiedPercent($satisfiedPctRaw),
                'trackColor' => '#e5e7eb',
            ],
            'rating_groups' => [
                [
                    'label' => '1-2',
                    'count' => (int) ($employee['score_counts'][1] ?? 0) + (int) ($employee['score_counts'][2] ?? 0),
                    'percentage' => (float) ($employee['score_percentages'][1] ?? 0) + (float) ($employee['score_percentages'][2] ?? 0),
                    'color' => $scoreColors[1],
                ],
                [
                    'label' => '3',
                    'count' => (int) ($employee['score_counts'][3] ?? 0),
                    'percentage' => (float) ($employee['score_percentages'][3] ?? 0),
                    'color' => $scoreColors[3],
                ],
                [
                    'label' => '4-5',
                    'count' => (int) ($employee['score_counts'][4] ?? 0) + (int) ($employee['score_counts'][5] ?? 0),
                    'percentage' => (float) ($employee['score_percentages'][4] ?? 0) + (float) ($employee['score_percentages'][5] ?? 0),
                    'color' => $scoreColors[5],
                ],
            ],
            'improvement_points' => $improvementPoints,
            'trend_chart_config' => [
                'labels' => $trend['labels'],
                'values' => $trend['averages'],
                'seriesLabel' => __('client.dashboard.score_trend.series_label'),
                'emptyLabel' => __('client.dashboard.score_trend.empty'),
            ],
        ];
    }

    /**
     * @return array<string, array{label: string}>
     */
    public function getReputationTabs(): array
    {
        return [
            'internal' => [
                'label' => __('client.dashboard.tabs.internal'),
            ],
            'external' => [
                'label' => __('client.dashboard.tabs.external'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getRangeTypeOptions(): array
    {
        return [
            InternalReputationDateRange::TYPE_ALL => __('client.dashboard.filters.range_types.all'),
            InternalReputationDateRange::TYPE_LAST_MONTH => __('client.dashboard.filters.range_types.last_month'),
            InternalReputationDateRange::TYPE_LAST_WEEK => __('client.dashboard.filters.range_types.last_week'),
            InternalReputationDateRange::TYPE_TODAY => __('client.dashboard.filters.range_types.today'),
            InternalReputationDateRange::TYPE_CUSTOM => __('client.dashboard.filters.range_types.custom'),
        ];
    }

    public function getActiveRangeLabel(): string
    {
        $range = $this->getInternalReputationDateRange();

        if (! $range->isCustom()) {
            return $this->getRangeTypeOptions()[$range->rangeType]
                ?? __('client.dashboard.filters.range_types.all');
        }

        [$from, $until] = $range->bounds();

        if ($from && $until) {
            return __('client.dashboard.filters.active_custom_between', [
                'from' => $from->format('d/m/Y'),
                'until' => $until->format('d/m/Y'),
            ]);
        }

        if ($from) {
            return __('client.dashboard.filters.active_custom_from', [
                'from' => $from->format('d/m/Y'),
            ]);
        }

        if ($until) {
            return __('client.dashboard.filters.active_custom_until', [
                'until' => $until->format('d/m/Y'),
            ]);
        }

        return __('client.dashboard.filters.custom_invalid');
    }

    /**
     * @return array{label: string, total_surveys: int, date_from: string, date_to: string}
     */
    public function getRangeContextSummary(): array
    {
        $range = $this->getInternalReputationDateRange();
        [$from, $to] = $range->bounds();

        if ($range->isAll()) {
            $from = $this->getClientStartDate();
            $to = now();
        }

        $metrics = $this->getInternalCsatMetrics();

        return [
            'label' => $this->getActiveRangeLabel(),
            'total_surveys' => (int) $metrics['total'],
            'date_from' => $from ? $from->format('d/m/Y') : __('common.placeholders.empty'),
            'date_to' => $to ? $to->format('d/m/Y') : now()->format('d/m/Y'),
        ];
    }

    /**
     * @return array{avg_score: string, avg_score_raw: float|null, gauge_percent: float, gauge_color: string, satisfied_pct: string, satisfied_pct_raw: float|null, total_surveys: int, score_breakdown: array<int, array{score: int, count: int, percentage: float}>}
     */
    public function getMainReputationSummary(): array
    {
        $metrics = $this->getInternalCsatMetrics();
        $avgScoreRaw = $metrics['avg_score'] !== null ? (float) $metrics['avg_score'] : null;
        $satisfiedPctRaw = $metrics['satisfied_pct'] !== null ? (float) $metrics['satisfied_pct'] : null;

        return [
            'avg_score' => $avgScoreRaw !== null
                ? number_format($avgScoreRaw, 1, ',', ' ')
                : __('common.placeholders.empty'),
            'avg_score_raw' => $avgScoreRaw,
            'gauge_percent' => $avgScoreRaw !== null ? round(($avgScoreRaw / 5) * 100, 1) : 0.0,
            'gauge_color' => match (true) {
                $avgScoreRaw === null => '#9ca3af',
                $avgScoreRaw >= 4 => '#22c55e',
                $avgScoreRaw >= 3 => '#f59e0b',
                default => '#ef4444',
            },
            'satisfied_pct' => $this->formatSatisfiedPercent($satisfiedPctRaw),
            'satisfied_pct_raw' => $satisfiedPctRaw,
            'total_surveys' => (int) $metrics['total'],
            'score_breakdown' => app(InternalReputationMetrics::class)->getScoreBreakdown(
                $this->getClientRecord()->id,
                $this->getInternalReputationDateRange(),
            ),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, description: string}>
     */
    public function getCsatSummary(): array
    {
        $metrics = $this->getInternalCsatMetrics();

        return [
            [
                'label' => __('client.dashboard.csat.avg_score'),
                'value' => $metrics['avg_score'] !== null
                    ? number_format((float) $metrics['avg_score'], 1, ',', ' ')
                    : __('common.placeholders.empty'),
                'description' => __('client.dashboard.csat.avg_score_description'),
            ],
            [
                'label' => __('client.dashboard.csat.total_surveys'),
                'value' => (string) $metrics['total'],
                'description' => __('client.dashboard.csat.total_surveys_description'),
            ],
            [
                'label' => __('client.dashboard.csat.satisfied_percent'),
                'value' => $this->formatSatisfiedPercent(
                    $metrics['satisfied_pct'] !== null ? (float) $metrics['satisfied_pct'] : null,
                ),
                'description' => __('client.dashboard.csat.satisfied_percent_description'),
            ],
            [
                'label' => __('client.dashboard.csat.surveys_today'),
                'value' => (string) $metrics['today_count'],
                'description' => __('client.dashboard.csat.surveys_today_description'),
            ],
        ];
    }

    public function getInternalReputationDateRange(): InternalReputationDateRange
    {
        return InternalReputationDateRange::fromState(
            $this->range_type,
            $this->date_from,
            $this->date_to,
        );
    }

    /**
     * @return array{status: string, status_label: string, mentions: int, detected_points: int, top_point_label: string}
     */
    public function getSurveySummary(): array
    {
        $config = $this->getClientRecord()
            ->improvementConfig()
            ->withCount('options')
            ->first();

        $optionsCount = (int) ($config?->options_count ?? 0);
        $status = match (true) {
            ! $config => 'missing',
            $optionsCount < 2 => 'incomplete',
            default => 'configured',
        };

        $rangeSummary = app(InternalReputationMetrics::class)->getImprovementSummary(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
        );

        return [
            'status' => $status,
            'status_label' => __("client.dashboard.survey.status.{$status}"),
            'mentions' => $rangeSummary['mentions'],
            'detected_points' => $rangeSummary['detected_points'],
            'top_point_label' => $rangeSummary['top_point_label'] ?? __('client.dashboard.survey.no_top_point'),
        ];
    }

    /**
     * @return array{question: string, total_surveys: int, options: array<int, array{id: string, label: string, count: int, percentage: float}>}
     */
    public function getImprovementRanking(): array
    {
        return app(InternalReputationMetrics::class)->getImprovementOptionRanking(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
            app()->getLocale(),
        );
    }

    /**
     * @return array{surveys: int, rated_employees: int, avg_score: string}
     */
    public function getEmployeesSummary(): array
    {
        $rangeSummary = app(InternalReputationMetrics::class)->getEmployeeSummary(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
        );

        return [
            'surveys' => $rangeSummary['surveys'],
            'rated_employees' => $rangeSummary['rated_employees'],
            'avg_score' => $rangeSummary['avg_score'] !== null
                ? number_format((float) $rangeSummary['avg_score'], 1, ',', ' ')
                : __('common.placeholders.empty'),
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, photo_url: string|null, initials: string, surveys: int, avg_score: string, score_counts: array<int, int>, score_percentages: array<int, float>}>
     */
    public function getEmployeeRanking(): array
    {
        return collect(app(InternalReputationMetrics::class)->getEmployeeScoreRanking(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
        ))
            ->map(fn (array $employee): array => [
                'id' => $employee['id'],
                'name' => $employee['name'],
                'photo_url' => $employee['photo'] ? Storage::disk('public')->url($employee['photo']) : null,
                'initials' => $this->getEmployeeInitials($employee['name']),
                'surveys' => $employee['surveys'],
                'avg_score' => number_format((float) $employee['avg_score'], 2, ',', ' '),
                'score_counts' => $employee['score_counts'],
                'score_percentages' => $employee['score_percentages'],
            ])
            ->all();
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>, granularity: string, grouping: string, total: int}
     */
    public function getSurveyHistory(): array
    {
        return app(InternalReputationMetrics::class)->getSurveyHistory(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
            $this->survey_history_grouping,
        );
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
    public function getScoreTrend(): array
    {
        return app(InternalReputationMetrics::class)->getScoreTrend(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
        );
    }

    /**
     * @return array{last_call_at: mixed, next_call_at: mixed, next_overdue: bool, total: int}|null
     */
    public function getCallsSummary(): ?array
    {
        if (! $this->canSeeCalls()) {
            return null;
        }

        $client = $this->getClientRecord();
        $nextCallAt = $client->next_call_at;
        $callsQuery = app(InternalReputationMetrics::class)->applyDateRange(
            $client->calls()->getQuery(),
            $this->getInternalReputationDateRange(),
            'called_at',
        );

        return [
            'last_call_at' => (clone $callsQuery)->latest('called_at')->first()?->called_at,
            'next_call_at' => $nextCallAt,
            'next_overdue' => $nextCallAt ? $nextCallAt->isPast() : false,
            'total' => (clone $callsQuery)->count(),
        ];
    }

    public function canSeeCalls(): bool
    {
        $user = auth()->user();
        $client = $this->getClientRecord();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isDistributor()) {
            return $client->created_by === $user->id;
        }

        return false;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        if (! isset($parameters['record'])) {
            return false;
        }

        return ClientResource::canView($parameters['record']);
    }

    protected function getClientRecord(): Client
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return $client;
    }

    private function getDefaultCustomDateFrom(): string
    {
        $date = $this->getClientStartDate();

        return $date ? $date->toDateString() : now()->toDateString();
    }

    private function getClientStartDate(): ?\Illuminate\Support\Carbon
    {
        $client = $this->getClientRecord();

        return $client->fecha_inicio_alta ?: $client->created_at;
    }

    private function getEmployeeInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');
    }

    /**
     * @return array{avg_score: float|null, total: int, satisfied_pct: float|null, today_count: int}
     */
    private function getInternalCsatMetrics(): array
    {
        return app(InternalReputationMetrics::class)->getCsatMetrics(
            $this->getClientRecord()->id,
            $this->getInternalReputationDateRange(),
        );
    }

    private function resetEmployeeDetail(): void
    {
        $this->showEmployeeDetail = false;
        $this->selectedEmployeeId = null;
    }

    private function formatSatisfiedPercent(?float $value): string
    {
        if ($value === null) {
            return __('common.placeholders.empty');
        }

        $rounded = round($value, 1);
        $decimals = abs($rounded - round($rounded)) < 0.001 ? 0 : 1;

        return number_format($rounded, $decimals, ',', ' ').'%';
    }
}
