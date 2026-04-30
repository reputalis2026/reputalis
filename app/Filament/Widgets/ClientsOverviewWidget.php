<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClientsOverviewWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.clients-overview-widget';

    protected static ?string $heading = 'Clientes';

    public string $activeTab = 'activos';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin() && $this->activeTab === 'distribuidores') {
            $this->activeTab = 'activos';
        }

        // Solo clientes (owner rol cliente), nunca distribuidores en las pestañas de clientes
        $baseQuery = Client::query()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_CLIENTE))
            ->orderBy('namecommercial');

        if ($user?->isClientOwner()) {
            $baseQuery->where('owner_id', $user->id);
        }

        if ($user?->isDistributor()) {
            $baseQuery->where('created_by', $user->id);
        }

        $activos = $this->getClientesActivos(clone $baseQuery);
        $inactivos = $this->getClientesInactivos(clone $baseQuery);
        $bajaProxima = $this->getClientesBajaProxima(clone $baseQuery);

        $clients = match ($this->activeTab) {
            'inactivos' => $inactivos,
            'baja_proxima' => $bajaProxima,
            'distribuidores' => $user?->isSuperAdmin() ? collect() : $activos,
            default => $activos,
        };

        $distributors = collect();
        if ($user?->isSuperAdmin() && $this->activeTab === 'distribuidores') {
            $distributors = $this->getDistribuidores();
        }

        return [
            'clients' => $clients,
            'distributors' => $distributors,
            'activeTab' => $this->activeTab,
            'showDistributorsTab' => $user?->isSuperAdmin() ?? false,
        ];
    }

    /**
     * Lista de distribuidores (solo para superadmin). Mismo modelo Client con owner rol distribuidor.
     *
     * @return Collection<int, object>
     */
    private function getDistribuidores(): Collection
    {
        return Client::query()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_DISTRIBUIDOR))
            ->orderBy('namecommercial')
            ->get()
            ->map(fn (Client $c) => (object) [
                'id' => $c->id,
                'name' => $c->namecommercial,
                'fecha_inicio' => $c->fecha_inicio_alta?->format('d/m/Y'),
                'fecha_fin' => $c->fecha_fin?->format('d/m/Y'),
                'is_active' => $c->is_active,
                'telefono' => $c->telefono_negocio ?: $c->telefono_cliente ?: '—',
            ]);
    }

    /**
     * @param  Builder<Client>  $query
     * @return Collection<int, object>
     */
    private function getClientesActivos(Builder $query): Collection
    {
        return $query
            ->where('is_active', true)
            ->withCount([
                'csatSurveys as surveys_today' => fn (Builder $q) => $q->whereDate('created_at', Carbon::today()),
                'csatSurveys as satisfied_today' => fn (Builder $q) => $q
                    ->whereDate('created_at', Carbon::today())
                    ->whereRaw("COALESCE(positive_scores_used, '[4,5]'::jsonb) @> to_jsonb(score)"),
            ])
            ->orderBy('namecommercial')
            ->get()
            ->map(function (Client $client) {
                $today = $client->surveys_today ?? 0;
                $satisfied = $client->satisfied_today ?? 0;
                $pct = $today > 0 ? round(($satisfied / $today) * 100, 1) : null;

                return (object) [
                    'id' => $client->id,
                    'name' => $client->namecommercial,
                    'fecha_inicio' => $client->fecha_inicio_alta?->format('d/m/Y'),
                    'fecha_fin' => $client->fecha_fin?->format('d/m/Y'),
                    'is_active' => true,
                    'encuestas_hoy' => $today,
                    'satisfied_pct' => $pct,
                    'telefono' => $client->telefono_negocio ?: $client->telefono_cliente,
                ];
            });
    }

    /**
     * @param  Builder<Client>  $query
     * @return Collection<int, object>
     */
    private function getClientesInactivos(Builder $query): Collection
    {
        return $query
            ->where('is_active', false)
            ->orderBy('namecommercial')
            ->get()
            ->map(fn (Client $client) => (object) [
                'id' => $client->id,
                'name' => $client->namecommercial,
                'fecha_inicio' => $client->fecha_inicio_alta?->format('d/m/Y'),
                'fecha_fin' => $client->fecha_fin?->format('d/m/Y'),
                'telefono' => $client->telefono_negocio ?: $client->telefono_cliente ?: '—',
            ]);
    }

    /**
     * Clientes activos cuya fecha de fin está a 2 meses o menos.
     *
     * @param  Builder<Client>  $query
     * @return Collection<int, object>
     */
    private function getClientesBajaProxima(Builder $query): Collection
    {
        $limite = Carbon::today()->addMonths(2)->endOfDay();

        return $query
            ->where('is_active', true)
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '<=', $limite)
            ->orderBy('fecha_fin')
            ->get()
            ->map(fn (Client $client) => (object) [
                'id' => $client->id,
                'name' => $client->namecommercial,
                'fecha_inicio' => $client->fecha_inicio_alta?->format('d/m/Y'),
                'fecha_fin' => $client->fecha_fin?->format('d/m/Y'),
                'telefono' => $client->telefono_negocio ?: $client->telefono_cliente ?: '—',
            ]);
    }
}
