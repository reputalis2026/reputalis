<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\Concerns\HasClientPageTitle;
use App\Models\Client;
use App\Models\ClientImprovementConfig;
use App\Support\CsatMetrics;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class ClientDashboard extends Page
{
    use HasClientPageTitle;
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.client-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.dashboard');
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    public function getMaxContentWidth(): MaxWidth | string | null
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

    /**
     * @return array<int, array{label: string, value: string, description: string}>
     */
    public function getCsatSummary(): array
    {
        $metrics = CsatMetrics::getMetrics($this->getClientRecord()->id, CsatMetrics::PERIOD_7_DAYS);

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
                'value' => $metrics['satisfied_pct'] !== null
                    ? number_format((float) $metrics['satisfied_pct'], 1, ',', ' ') . '%'
                    : __('common.placeholders.empty'),
                'description' => __('client.dashboard.csat.satisfied_percent_description'),
            ],
            [
                'label' => __('client.dashboard.csat.surveys_today'),
                'value' => (string) $metrics['today_count'],
                'description' => __('client.dashboard.csat.surveys_today_description'),
            ],
        ];
    }

    /**
     * @return array{status: string, status_label: string, mode_label: string, options_count: int}
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

        $mode = $config
            ? ClientImprovementConfig::normalizeDisplayMode($config->display_mode)
            : null;

        return [
            'status' => $status,
            'status_label' => __("client.dashboard.survey.status.{$status}"),
            'mode_label' => $mode
                ? __("client.survey.display_modes.{$mode}")
                : __('common.placeholders.empty'),
            'options_count' => $optionsCount,
        ];
    }

    /**
     * @return array{active: int, inactive: int, total: int}
     */
    public function getEmployeesSummary(): array
    {
        $client = $this->getClientRecord();
        $active = $client->employees()->where('is_active', true)->count();
        $inactive = $client->employees()->where('is_active', false)->count();

        return [
            'active' => $active,
            'inactive' => $inactive,
            'total' => $active + $inactive,
        ];
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

        return [
            'last_call_at' => $client->last_call_at,
            'next_call_at' => $nextCallAt,
            'next_overdue' => $nextCallAt ? $nextCallAt->isPast() : false,
            'total' => $client->calls()->count(),
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
}
