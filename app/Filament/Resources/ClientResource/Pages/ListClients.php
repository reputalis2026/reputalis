<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Pages\ClientPuntosDeMejora;
use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public function mount(): void
    {
        parent::mount();

        // El rol cliente no debe ver el listado; redirigir a sus Puntos de mejora.
        $user = auth()->user();
        if ($user?->isClientOwner() && $user->ownedClient) {
            $this->redirect(ClientPuntosDeMejora::getUrl());
        }
    }

    public function getTitle(): string
    {
        return 'Clientes';
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        $tabs = [
            'todos' => Tab::make('Todos los clientes'),
        ];

        // Solo SuperAdmin debe ver la pestaña de eliminados/restauración.
        if ($user?->isSuperAdmin() === true) {
            $tabs['eliminados'] = Tab::make('Clientes eliminados')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed());
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear')
                ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->isDistributor() ?? false),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        if (auth()->user()?->isClientOwner()) {
            $user = auth()->user();
            $query = $query->where('owner_id', $user->id);
        }

        if (auth()->user()?->isDistributor()) {
            $query = $query->where('created_by', auth()->id());
        }

        return $query->orderBy('is_active', 'desc')->orderBy('namecommercial', 'asc');
    }

    public function getTableHeading(): ?string
    {
        return 'Lista de Clientes';
    }

    public function getTableEmptyStateHeading(): ?string
    {
        return 'No hay clientes';
    }

    public function getTableEmptyStateDescription(): ?string
    {
        return 'Crea tu primer cliente para comenzar.';
    }
}
