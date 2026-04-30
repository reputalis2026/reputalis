<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CsatSurveyResource;
use App\Models\Client;
use App\Support\CsatMetrics;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CsatStatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = null;

    public function getHeading(): ?string
    {
        return __('dashboard.stats.heading');
    }

    /**
     * Filtros del dashboard (period, client_id). Pasados desde la página.
     *
     * @var array<string, mixed>
     */
    public array $filters = [];

    /**
     * Filtros actuales desde sesión (el Dashboard los guarda al cambiar el selector).
     * Así las métricas se actualizan al cambiar farmacia/período sin depender de props Livewire.
     */
    private function getCurrentFilters(): array
    {
        $sessionKey = md5(\App\Filament\Pages\Dashboard::class) . '_filters';
        $session = session()->get($sessionKey, []);

        return [
            'period' => $session['period'] ?? $this->filters['period'] ?? CsatMetrics::PERIOD_7_DAYS,
            'client_id' => array_key_exists('client_id', $session) ? $session['client_id'] : ($this->filters['client_id'] ?? null),
        ];
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $filters = $this->getCurrentFilters();
        $period = $filters['period'];
        $selectedClientId = filled($filters['client_id'] ?? null) ? (string) $filters['client_id'] : null;
        $metricsScope = null;

        if ($user?->isSuperAdmin()) {
            $metricsScope = $selectedClientId;
        } elseif ($user?->isClientOwner()) {
            $metricsScope = $user->ownedClient?->id;
        } elseif ($user?->isDistributor()) {
            $metricsScope = Client::query()
                ->where('created_by', $user->id)
                ->pluck('id')
                ->all();
        }

        $metrics = CsatMetrics::getMetrics($metricsScope, $period);

        $baseUrl = CsatSurveyResource::getUrl('index');
        $tableClientId = $user?->isSuperAdmin() ? $selectedClientId : null;
        $tableFilters = $this->buildTableFiltersForPeriod($period, $tableClientId);

        $avgScore = $metrics['avg_score'] !== null
            ? number_format($metrics['avg_score'], 1, ',', ' ')
            : '—';
        $avgColor = $metrics['avg_score'] !== null
            ? ($metrics['avg_score'] >= 4 ? 'success' : ($metrics['avg_score'] >= 3 ? 'warning' : 'danger'))
            : 'gray';

        $satisfiedPct = $metrics['satisfied_pct'] !== null
            ? $metrics['satisfied_pct'] . '%'
            : '—';
        $satisfiedColor = $metrics['satisfied_pct'] !== null
            ? ($metrics['satisfied_pct'] >= 80 ? 'success' : ($metrics['satisfied_pct'] >= 60 ? 'warning' : 'danger'))
            : 'gray';

        return [
            Stat::make(__('dashboard.stats.avg_score'), $avgScore)
                ->description(__('dashboard.stats.avg_score_description'))
                ->descriptionIcon('heroicon-m-star')
                ->color($avgColor)
                ->url($baseUrl . '?' . http_build_query(['tableFilters' => $tableFilters])),

            Stat::make(__('dashboard.stats.total_surveys'), (string) $metrics['total'])
                ->description(__('dashboard.stats.total_surveys_description'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($metrics['total'] > 0 ? 'success' : 'gray')
                ->url($baseUrl . '?' . http_build_query(['tableFilters' => $tableFilters])),

            Stat::make(__('dashboard.stats.satisfied_percent'), $satisfiedPct)
                ->description(__('dashboard.stats.satisfied_percent_description'))
                ->descriptionIcon('heroicon-m-face-smile')
                ->color($satisfiedColor)
                ->url($baseUrl . '?' . http_build_query([
                    'tableFilters' => array_merge($tableFilters, ['score' => '4-5']),
                ])),

            Stat::make(__('dashboard.stats.surveys_today'), (string) $metrics['today_count'])
                ->description(__('dashboard.stats.surveys_today_description'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($metrics['today_count'] > 0 ? 'success' : 'gray')
                ->url($baseUrl . '?' . http_build_query([
                    'tableFilters' => $this->buildTableFiltersForPeriod(CsatMetrics::PERIOD_TODAY, $tableClientId),
                ])),
        ];
    }

    /**
     * @param  string|null  $clientId
     * @return array<string, mixed>
     */
    private function buildTableFiltersForPeriod(string $period, ?string $clientId): array
    {
        $filters = [];

        if ($period !== CsatMetrics::PERIOD_ALL) {
            [$from, $to] = CsatMetrics::dateRangeForPeriod($period);
            $filters['created_at'] = [
                'created_from' => Carbon::parse($from)->format('Y-m-d'),
                'created_until' => Carbon::parse($to)->format('Y-m-d'),
            ];
        }

        if ($clientId && auth()->user()?->isSuperAdmin()) {
            $filters['client_id'] = $clientId;
        }

        return $filters;
    }
}
