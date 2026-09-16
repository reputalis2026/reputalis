<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\ClientExternalReputationAlert;
use App\Models\ClientExternalReputationSnapshot;
use App\Support\ExternalReputation\ExternalReputationSyncService;
use App\Support\ExternalReputation\RatingProjection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action as HeaderAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

class ReputacionExterna extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.reputacion-externa';

    /** @var 'days'|'month'|'year' */
    public string $historyMode = 'days';

    /** @var array<string, 'week'|'month'|'six_months'|'year'> */
    public array $client_chart_ranges = [
        'google' => 'six_months',
        'real' => 'six_months',
        'reviews' => 'six_months',
    ];

    public ?int $filterYear = null;

    public ?int $filterMonth = null;

    public float $targetRating = RatingProjection::DEFAULT_TARGET;

    public static function getNavigationLabel(): string
    {
        return __('client.menu.external_reputation');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        if (\App\Support\ClientPanel::isActive()) {
            return __('client.dashboard.tabs.external');
        }

        return (string) ($this->getRecord()?->namecommercial ?? __('client.resource.model_label'));
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        if (\App\Support\ClientPanel::isActive()) {
            return '';
        }

        return parent::getHeading();
    }

    /**
     * @return array<string, array{label: string, badge?: int}>
     */
    public function getReputationTabs(): array
    {
        $unread = $this->getUnreadAlertsCount();

        return [
            'internal' => [
                'label' => __('client.dashboard.tabs.internal'),
            ],
            'external' => [
                'label' => __('client.dashboard.tabs.external'),
            ],
            'sector' => [
                'label' => __('client.dashboard.tabs.sector'),
            ],
        ];
    }

    public function switchReputationTab(string $tab): void
    {
        if ($tab === 'external') {
            return;
        }

        $url = ClientDashboard::getUrl(['record' => $this->getRecord()]);
        if (in_array($tab, ['internal', 'sector'], true)) {
            $url .= (str_contains($url, '?') ? '&' : '?').'reputationTab='.$tab;
        }

        $this->redirect($url);
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        \App\Support\ClientPanel::enterPreview($this->getClientRecord());

        $now = Carbon::now('Europe/Madrid');
        $this->filterYear = (int) $now->year;
        $this->filterMonth = (int) $now->month;
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

    protected function getClientRecord(): Client
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return $client;
    }

    public function canSync(): bool
    {
        return ClientResource::canEdit($this->getClientRecord());
    }

    /**
     * @return array<HeaderAction>
     */
    protected function getHeaderActions(): array
    {
        if (\App\Support\ClientPanel::isActive()) {
            return [];
        }

        return [
            HeaderAction::make('syncNow')
                ->label(__('client.external_reputation.sync_now'))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => $this->canSync() && filled($this->getClientRecord()->google_place_id))
                ->action('syncNow'),
        ];
    }

    public function simulateSync(): void
    {
        abort_unless($this->canSync(), 403);

        $client = $this->getClientRecord();
        $result = app(ExternalReputationSyncService::class)->simulateIncrementalSync($client->fresh());

        if ($result['ok']) {
            $alert = $result['alert'];
            if ($alert instanceof ClientExternalReputationAlert && $alert->isNegativeIncrease()) {
                Notification::make()
                    ->warning()
                    ->title(__('client.external_reputation.simulate_ok_title'))
                    ->body(__('client.external_reputation.sync_ok_with_alert'))
                    ->send();
            } elseif ($alert instanceof ClientExternalReputationAlert && $alert->isTotalDropAnomaly()) {
                Notification::make()
                    ->success()
                    ->title(__('client.external_reputation.simulate_ok_title'))
                    ->body(__('client.external_reputation.sync_ok_with_anomaly'))
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title(__('client.external_reputation.simulate_ok_title'))
                    ->body(__('client.external_reputation.simulate_ok_body'))
                    ->send();
            }
        } else {
            Notification::make()
                ->danger()
                ->title(__('client.external_reputation.simulate_fail_title'))
                ->body($result['error'] ?? __('client.external_reputation.sync_fail_body'))
                ->send();
        }

        $this->record = $client->fresh();
    }

    public function simulate5Star(): void
    {
        $this->simulateReview(5);
    }

    public function simulate1Star(): void
    {
        $this->simulateReview(1);
    }

    private function simulateReview(int $stars): void
    {
        abort_unless($this->canSync(), 403);

        $client = $this->getClientRecord();
        $result = app(ExternalReputationSyncService::class)->simulateSingleReview($client->fresh(), $stars);

        if ($result['ok']) {
            Notification::make()
                ->success()
                ->title(__('client.external_reputation.simulate_review_ok', ['stars' => $stars]))
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title(__('client.external_reputation.simulate_fail_title'))
                ->body($result['error'] ?? __('client.external_reputation.sync_fail_body'))
                ->send();
        }

        $this->redirect(request()->header('Referer', request()->url()), navigate: true);
    }

    public function syncNow(): void
    {
        abort_unless($this->canSync(), 403);

        $client = $this->getClientRecord();
        if (! filled($client->google_place_id)) {
            Notification::make()
                ->danger()
                ->title(__('client.external_reputation.missing_place_id_title'))
                ->body(__('client.external_reputation.missing_place_id_body'))
                ->send();

            return;
        }

        $result = app(ExternalReputationSyncService::class)->syncClient($client->fresh());

        if ($result['ok']) {
            $alert = $result['alert'];
            if ($alert instanceof ClientExternalReputationAlert && $alert->isNegativeIncrease()) {
                Notification::make()
                    ->warning()
                    ->title(__('client.external_reputation.sync_ok_title'))
                    ->body(__('client.external_reputation.sync_ok_with_alert'))
                    ->send();
            } elseif ($alert instanceof ClientExternalReputationAlert && $alert->isTotalDropAnomaly()) {
                Notification::make()
                    ->success()
                    ->title(__('client.external_reputation.sync_ok_title'))
                    ->body(__('client.external_reputation.sync_ok_with_anomaly'))
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title(__('client.external_reputation.sync_ok_title'))
                    ->body(__('client.external_reputation.sync_ok_body'))
                    ->send();
            }
        } else {
            Notification::make()
                ->danger()
                ->title(__('client.external_reputation.sync_fail_title'))
                ->body($result['error'] ?? __('client.external_reputation.sync_fail_body'))
                ->send();
        }

        $this->record = $client->fresh();
    }

    public function setHistoryMode(string $mode): void
    {
        if (! in_array($mode, ['days', 'month', 'year'], true)) {
            return;
        }
        $this->historyMode = $mode;
    }

    public function setClientChartRange(string $chart, string $range): void
    {
        if (! in_array($chart, ['google', 'real', 'reviews'], true)) {
            return;
        }

        if (! in_array($range, ['week', 'month', 'six_months', 'year'], true)) {
            return;
        }

        $this->client_chart_ranges[$chart] = $range;
        $this->dispatch('reputalis-external-charts-refresh');
    }

    public function clientChartRangeFor(string $chart): string
    {
        $range = $this->client_chart_ranges[$chart] ?? 'six_months';

        return in_array($range, ['week', 'month', 'six_months', 'year'], true)
            ? $range
            : 'six_months';
    }

    /**
     * @return array<string, string>
     */
    public function getClientChartRangeOptions(): array
    {
        return [
            'week' => __('client.external_reputation.chart_pills.week'),
            'month' => __('client.external_reputation.chart_pills.month'),
            'six_months' => __('client.external_reputation.chart_pills.six_months'),
            'year' => __('client.external_reputation.chart_pills.year'),
        ];
    }

    public function getLatestSnapshot(): ?ClientExternalReputationSnapshot
    {
        return $this->getClientRecord()->latestExternalReputationSnapshot();
    }

    /**
     * @return Collection<int, ClientExternalReputationAlert>
     */
    public function getRecentAlerts(): Collection
    {
        return $this->getClientRecord()
            ->externalReputationAlerts()
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('detected_at')
            ->limit(20)
            ->get();
    }

    public function getUnreadAlertsCount(): int
    {
        return (int) $this->getClientRecord()
            ->externalReputationAlerts()
            ->unread()
            ->count();
    }

    public function markAlertAsRead(string $alertId): void
    {
        abort_unless(ClientResource::canView($this->getClientRecord()), 403);

        $alert = $this->getClientRecord()
            ->externalReputationAlerts()
            ->whereKey($alertId)
            ->first();

        if (! $alert) {
            return;
        }

        $alert->markAsRead();
    }

    public function markAllAlertsAsRead(): void
    {
        abort_unless(ClientResource::canView($this->getClientRecord()), 403);

        $this->getClientRecord()
            ->externalReputationAlerts()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function getFiveStarsNeeded(): ?int
    {
        $snapshot = $this->getLatestSnapshot();
        if (! $snapshot) {
            return null;
        }

        return RatingProjection::fiveStarsNeededForTarget(
            $snapshot->starsBreakdown(),
            $this->targetRating
        );
    }

    /**
     * @return array{
     *     has_snapshot: bool,
     *     google_rating_formatted: string,
     *     total_reviews: int,
     *     positive_pct_formatted: string,
     *     distribution: array<int, array{score: int, count: int, height: float, color: string}>,
     *     real_rating_formatted: string,
     *     target_formatted: string,
     *     stars_needed_formatted: string,
     *     progress_pct: int,
     *     progress_from: string,
     *     progress_to: string,
     *     arc_total: int,
     *     arc_progress: int,
     *     star_x: float,
     *     star_y: float
     * }
     */
    public function getClientHeroSummary(): array
    {
        $scoreColors = [
            1 => '#EE2737',
            2 => '#FF6A13',
            3 => '#FFB81C',
            4 => '#A4D65E',
            5 => '#00B140',
        ];
        $emptyBars = collect([1, 2, 3, 4, 5])->map(fn (int $score): array => [
            'score' => $score,
            'count' => 0,
            'height' => 10.0,
            'color' => $scoreColors[$score],
        ])->all();
        $arcTotal = 173;
        $cx = 70.0;
        $cy = 75.0;
        $r = 55.0;

        $empty = [
            'has_snapshot' => false,
            'google_rating_formatted' => __('common.placeholders.empty'),
            'total_reviews' => 0,
            'positive_pct_formatted' => __('common.placeholders.empty'),
            'distribution' => $emptyBars,
            'real_rating_formatted' => __('common.placeholders.empty'),
            'target_formatted' => __('common.placeholders.empty'),
            'stars_needed_formatted' => __('common.placeholders.empty'),
            'progress_pct' => 0,
            'progress_from' => __('common.placeholders.empty'),
            'progress_to' => __('common.placeholders.empty'),
            'arc_total' => $arcTotal,
            'arc_progress' => 0,
            'star_x' => round($cx + $r * cos(deg2rad(-180)), 1),
            'star_y' => round($cy + $r * sin(deg2rad(-180)), 1),
        ];

        $snapshot = $this->getLatestSnapshot();
        if (! $snapshot) {
            return $empty;
        }

        $stars = $snapshot->starsBreakdown();
        $totalReviews = (int) $snapshot->reviews_total;
        $maxCount = max(1, max($stars));
        $distribution = collect([1, 2, 3, 4, 5])->map(function (int $score) use ($stars, $maxCount, $scoreColors): array {
            $count = (int) ($stars[$score] ?? 0);

            return [
                'score' => $score,
                'count' => $count,
                'height' => $count > 0 ? max(18, ($count / $maxCount) * 100) : 10,
                'color' => $scoreColors[$score],
            ];
        })->all();

        $positiveCount = (int) ($stars[4] ?? 0) + (int) ($stars[5] ?? 0);
        $positivePct = $totalReviews > 0 ? (int) round(($positiveCount / $totalReviews) * 100) : null;
        $rawReal = RatingProjection::calculatedRating($stars);
        $googleRating = $snapshot->rating !== null ? (float) $snapshot->rating : null;
        $currentDisplayed = $googleRating !== null
            ? round($googleRating, 1)
            : ($rawReal !== null ? round(floor(round($rawReal, 4) * 10) / 10, 1) : null);
        $nextLevel = $currentDisplayed !== null ? round(min(5.0, $currentDisplayed + 0.1), 1) : null;

        if ($currentDisplayed !== null && $currentDisplayed >= 5) {
            $nextLevel = 5.0;
        }

        $starsNeeded = $nextLevel !== null
            ? RatingProjection::fiveStarsNeededForTarget($stars, $nextLevel)
            : null;
        if ($starsNeeded !== null && $starsNeeded < 0) {
            $starsNeeded = 0;
        }

        $progressPct = 0;
        if ($rawReal !== null && $currentDisplayed !== null && $nextLevel !== null && $nextLevel > $currentDisplayed) {
            $diff = $rawReal - $currentDisplayed;
            $progressPct = min(100, max(0, (int) (floor($diff / 0.1 * 100 / 5) * 5)));
        } elseif ($currentDisplayed !== null && $currentDisplayed >= 5) {
            $progressPct = 100;
        }

        $angle = -180 + ($progressPct / 100) * 180;
        $rad = deg2rad($angle);

        return [
            'has_snapshot' => true,
            'google_rating_formatted' => $googleRating !== null
                ? number_format($googleRating, 1, ',', '')
                : __('common.placeholders.empty'),
            'total_reviews' => $totalReviews,
            'positive_pct_formatted' => $positivePct !== null
                ? number_format($positivePct, 0, ',', '.').'%'
                : __('common.placeholders.empty'),
            'distribution' => $distribution,
            'real_rating_formatted' => $rawReal !== null
                ? number_format($rawReal, 2, ',', '')
                : __('common.placeholders.empty'),
            'target_formatted' => $nextLevel !== null
                ? number_format($nextLevel, 1, ',', '')
                : __('common.placeholders.empty'),
            'stars_needed_formatted' => $starsNeeded !== null
                ? (string) $starsNeeded
                : __('common.placeholders.empty'),
            'progress_pct' => $progressPct,
            'progress_from' => $currentDisplayed !== null
                ? number_format($currentDisplayed, 1, ',', '')
                : __('common.placeholders.empty'),
            'progress_to' => $nextLevel !== null
                ? number_format($nextLevel, 1, ',', '')
                : __('common.placeholders.empty'),
            'arc_total' => $arcTotal,
            'arc_progress' => (int) round($progressPct * $arcTotal / 100),
            'star_x' => round($cx + $r * cos($rad), 1),
            'star_y' => round($cy + $r * sin($rad), 1),
        ];
    }

    public function getEditClientUrl(): string
    {
        return EditClient::getUrl(['record' => $this->getClientRecord()]);
    }

    /**
     * Filas de histórico según modo (día / mes / año), sin hora.
     *
     * @return list<array{
     *   label: string,
     *   rating: mixed,
     *   reviews_total: int,
     *   calculated_rating: mixed,
     *   stars_1: int,
     *   stars_2: int,
     *   stars_3: int,
     *   stars_4: int,
     *   stars_5: int
     * }>
     */
    public function getHistoryRows(): array
    {
        $clientId = $this->getClientRecord()->id;
        $query = ClientExternalReputationSnapshot::query()
            ->where('client_id', $clientId)
            ->orderByDesc('captured_at');

        if ($this->historyMode === 'month' && $this->filterYear && $this->filterMonth) {
            $query->whereYear('snapshot_date', $this->filterYear)
                ->whereMonth('snapshot_date', $this->filterMonth);
        } elseif ($this->historyMode === 'year' && $this->filterYear) {
            $query->whereYear('snapshot_date', $this->filterYear);
        } else {
            // days: últimos 30 días civiles (Madrid)
            $from = Carbon::now('Europe/Madrid')->subDays(29)->toDateString();
            $query->whereDate('snapshot_date', '>=', $from);
        }

        $snapshots = $query->get();

        if ($this->historyMode === 'year') {
            return $this->aggregateByMonth($snapshots);
        }

        return $this->latestPerDay($snapshots);
    }

    /**
     * @param  Collection<int, ClientExternalReputationSnapshot>  $snapshots
     * @return list<array<string, mixed>>
     */
    private function latestPerDay(Collection $snapshots): array
    {
        $byDay = [];
        foreach ($snapshots as $snap) {
            $key = $snap->snapshot_date?->format('Y-m-d') ?? '';
            if ($key === '' || isset($byDay[$key])) {
                continue; // ya ordenado desc por captured_at
            }
            $byDay[$key] = [
                'label' => $snap->snapshot_date?->format('d/m/Y') ?? $key,
                'rating' => $snap->rating,
                'reviews_total' => (int) $snap->reviews_total,
                'calculated_rating' => $snap->calculated_rating,
                'stars_1' => (int) $snap->stars_1,
                'stars_2' => (int) $snap->stars_2,
                'stars_3' => (int) $snap->stars_3,
                'stars_4' => (int) $snap->stars_4,
                'stars_5' => (int) $snap->stars_5,
            ];
        }

        return array_values($byDay);
    }

    /**
     * @param  Collection<int, ClientExternalReputationSnapshot>  $snapshots
     * @return list<array<string, mixed>>
     */
    private function aggregateByMonth(Collection $snapshots): array
    {
        $byMonth = [];
        foreach ($snapshots as $snap) {
            $key = $snap->snapshot_date?->format('Y-m') ?? '';
            if ($key === '' || isset($byMonth[$key])) {
                continue;
            }
            $byMonth[$key] = [
                'label' => $snap->snapshot_date?->format('m/Y') ?? $key,
                'rating' => $snap->rating,
                'reviews_total' => (int) $snap->reviews_total,
                'calculated_rating' => $snap->calculated_rating,
                'stars_1' => (int) $snap->stars_1,
                'stars_2' => (int) $snap->stars_2,
                'stars_3' => (int) $snap->stars_3,
                'stars_4' => (int) $snap->stars_4,
                'stars_5' => (int) $snap->stars_5,
            ];
        }

        return array_values($byMonth);
    }

    /**
     * Datos para ApexCharts a partir del mismo histórico (orden cronológico).
     *
     * @return array{
     *   mode: string,
     *   range_key: string,
     *   labels: list<string>,
     *   ratings: list<float|null>,
     *   calculated: list<float|null>,
     *   totals: list<int>,
     *   stars_1: list<int>,
     *   stars_2: list<int>,
     *   stars_3: list<int>,
     *   stars_4: list<int>,
     *   stars_5: list<int>,
     *   empty: bool
     * }
     */
    public function getHistoryChartConfig(): array
    {
        $rows = array_reverse($this->getHistoryRows());

        $toFloatOrNull = static function (mixed $value): ?float {
            if ($value === null || $value === '') {
                return null;
            }

            return round((float) $value, 4);
        };

        return [
            'mode' => $this->historyMode,
            'range_key' => $this->historyMode.'|'.($this->filterYear ?? '').'|'.($this->filterMonth ?? ''),
            'labels' => array_map(static fn (array $row): string => (string) $row['label'], $rows),
            'ratings' => array_map(static fn (array $row): ?float => $toFloatOrNull($row['rating'] ?? null), $rows),
            'calculated' => array_map(static fn (array $row): ?float => $toFloatOrNull($row['calculated_rating'] ?? null), $rows),
            'totals' => array_map(static fn (array $row): int => (int) ($row['reviews_total'] ?? 0), $rows),
            'stars_1' => array_map(static fn (array $row): int => (int) ($row['stars_1'] ?? 0), $rows),
            'stars_2' => array_map(static fn (array $row): int => (int) ($row['stars_2'] ?? 0), $rows),
            'stars_3' => array_map(static fn (array $row): int => (int) ($row['stars_3'] ?? 0), $rows),
            'stars_4' => array_map(static fn (array $row): int => (int) ($row['stars_4'] ?? 0), $rows),
            'stars_5' => array_map(static fn (array $row): int => (int) ($row['stars_5'] ?? 0), $rows),
            'empty' => $rows === [],
            'series' => [
                'google' => __('client.external_reputation.chart_rating_google'),
                'calculated' => __('client.external_reputation.chart_rating_calculated'),
                'total' => __('client.external_reputation.chart_total_series'),
                'stars_1' => '1★',
                'stars_2' => '2★',
                'stars_3' => '3★',
                'stars_4' => '4★',
                'stars_5' => '5★',
            ],
            'empty_label' => __('client.external_reputation.charts_empty'),
        ];
    }

    /**
     * @return array{
     *     google: array<string, mixed>,
     *     real: array<string, mixed>,
     *     reviews: array<string, mixed>
     * }
     */
    public function getClientEvolutionCharts(): array
    {
        $locale = str_replace('_', '-', app()->getLocale());
        $emptyLabel = __('client.external_reputation.charts_empty');
        $preparedByRange = [];

        $prepared = function (string $chart) use (&$preparedByRange): array {
            $range = $this->clientChartRangeFor($chart);
            if (! isset($preparedByRange[$range])) {
                $rows = $this->getClientEvolutionRows($range);
                $totals = array_column($rows, 'total');
                $maxTotal = 0;
                foreach ($totals as $total) {
                    if ($total !== null && $total > $maxTotal) {
                        $maxTotal = (int) $total;
                    }
                }

                $preparedByRange[$range] = [
                    'granularity' => $this->clientChartGranularity($range),
                    'labels' => array_column($rows, 'label'),
                    'google' => array_column($rows, 'rating'),
                    'real' => array_column($rows, 'calculated'),
                    'totals' => $totals,
                    'counts' => array_column($rows, 'count'),
                    'maxTotal' => $maxTotal,
                ];
            }

            return $preparedByRange[$range];
        };

        $google = $prepared('google');
        $real = $prepared('real');
        $reviews = $prepared('reviews');

        $base = static fn (array $data): array => [
            'granularity' => $data['granularity'],
            'clientStyle' => true,
            'locale' => $locale,
            'counts' => $data['counts'],
            'emptyLabel' => $emptyLabel,
        ];

        return [
            'google' => array_merge($base($google), [
                'labels' => $google['labels'],
                'values' => $google['google'],
                'overallAverage' => $this->lastNumeric($google['google']),
                'accent' => '#2eb5d6',
                'yCeiling' => 5,
                'yDecimals' => 1,
                'badgeDecimals' => 1,
                'tightScale' => true,
                'badgeBackground' => '#1e293b',
            ]),
            'real' => array_merge($base($real), [
                'labels' => $real['labels'],
                'values' => $real['real'],
                'overallAverage' => $this->lastNumeric($real['real']),
                'accent' => '#2eb5d6',
                'yCeiling' => 5,
                'yDecimals' => 2,
                'badgeDecimals' => 2,
                'tightScale' => true,
                'badgeBackground' => '#1e293b',
            ]),
            'reviews' => array_merge($base($reviews), [
                'labels' => $reviews['labels'],
                'values' => $reviews['totals'],
                'overallAverage' => $this->lastNumeric($reviews['totals']),
                'accent' => '#12a37a',
                'yCeiling' => max(50, (int) (ceil(($reviews['maxTotal'] + 10) / 30) * 30)),
                'yDecimals' => 0,
                'badgeDecimals' => 0,
                'tightScale' => false,
                'badgeBackground' => '#0f6b53',
            ]),
        ];
    }

    /**
     * @param  list<float|int|null>  $values
     */
    private function lastNumeric(array $values): float|int|null
    {
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if ($values[$i] !== null && is_numeric($values[$i])) {
                return $values[$i];
            }
        }

        return null;
    }

    private function clientChartGranularity(string $range): string
    {
        return match ($range) {
            'week' => 'day',
            'month' => 'week',
            default => 'month',
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function clientChartBounds(string $range): array
    {
        $until = Carbon::now('Europe/Madrid')->endOfDay();

        $from = match ($range) {
            'week' => $until->copy()->subDays(6)->startOfDay(),
            'month' => $until->copy()->subDays(29)->startOfDay(),
            'year' => $until->copy()->startOfMonth()->subMonths(11),
            default => $until->copy()->startOfMonth()->subMonths(5),
        };

        return [$from, $until];
    }

    /**
     * @return list<array{label: string, rating: float|null, calculated: float|null, total: int|null, count: int}>
     */
    private function getClientEvolutionRows(string $range): array
    {
        [$from, $until] = $this->clientChartBounds($range);
        $granularity = $this->clientChartGranularity($range);
        $snapshots = ClientExternalReputationSnapshot::query()
            ->where('client_id', $this->getClientRecord()->id)
            ->whereDate('snapshot_date', '>=', $from->toDateString())
            ->whereDate('snapshot_date', '<=', $until->toDateString())
            ->orderByDesc('captured_at')
            ->get();

        $latestByKey = [];
        foreach ($snapshots as $snapshot) {
            $date = $snapshot->snapshot_date;
            if (! $date) {
                continue;
            }
            $key = $this->clientChartBucketKey($date, $granularity);
            if (! isset($latestByKey[$key])) {
                $latestByKey[$key] = $snapshot;
            }
        }

        $carry = ClientExternalReputationSnapshot::query()
            ->where('client_id', $this->getClientRecord()->id)
            ->whereDate('snapshot_date', '<', $from->toDateString())
            ->orderByDesc('snapshot_date')
            ->orderByDesc('captured_at')
            ->first();

        $start = $this->clientChartBucketStart($from, $granularity);
        $end = $this->clientChartBucketStart($until, $granularity);
        $step = match ($granularity) {
            'day' => '1 day',
            'week' => '1 week',
            default => '1 month',
        };
        $rows = [];

        foreach (CarbonPeriod::create($start, $step, $end) as $bucket) {
            $key = $this->clientChartBucketKey($bucket, $granularity);
            $snapshot = $latestByKey[$key] ?? null;
            if ($snapshot) {
                $carry = $snapshot;
            }
            $source = $snapshot ?? $carry;
            $label = in_array($granularity, ['day', 'week'], true)
                ? $bucket->format('j/n')
                : $this->formatClientMonthLabel($bucket);

            $rows[] = [
                'label' => $label,
                'rating' => $source && $source->rating !== null ? round((float) $source->rating, 2) : null,
                'calculated' => $source
                    ? ($source->calculated_rating !== null
                        ? round((float) $source->calculated_rating, 4)
                        : RatingProjection::calculatedRating($source->starsBreakdown()))
                    : null,
                'total' => $source ? (int) $source->reviews_total : null,
                'count' => $snapshot ? 1 : 0,
            ];
        }

        return $rows;
    }

    private function clientChartBucketKey(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }

    private function clientChartBucketStart(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function formatClientMonthLabel(Carbon $bucket): string
    {
        return ucfirst(str_replace('.', '', $bucket->copy()->locale(app()->getLocale())->isoFormat('MMM')));
    }

    /**
     * @return list<int>
     */
    public function getAvailableYears(): array
    {
        $years = ClientExternalReputationSnapshot::query()
            ->where('client_id', $this->getClientRecord()->id)
            ->pluck('snapshot_date')
            ->filter()
            ->map(fn ($d) => (int) Carbon::parse($d)->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($years === []) {
            $years = [(int) Carbon::now('Europe/Madrid')->year];
        }

        return $years;
    }
}
