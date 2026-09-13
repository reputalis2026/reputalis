<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\ClientExternalReputationAlert;
use App\Models\ClientExternalReputationSnapshot;
use App\Support\ExternalReputation\ExternalReputationSyncService;
use App\Support\ExternalReputation\RatingProjection;
use Carbon\Carbon;
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
