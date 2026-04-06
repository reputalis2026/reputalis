<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Pages\ClientPuntosDeMejora;
use App\Filament\Resources\ClientResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public function mount(): void
    {
        parent::mount();

        // El rol cliente no debe ver el listado; redirigir a su página Encuesta.
        $user = auth()->user();
        if ($user?->isClientOwner() && $user->ownedClient) {
            $this->redirect(ClientPuntosDeMejora::getUrl());
        }
    }

    public function getTitle(): string
    {
        return 'Clientes';
    }

    public function getBreadcrumbs(): array
    {
        // Quitar breadcrumb superior "Cliente > Listado"
        return [];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        // Reduce separación visual entre sidebar y tabla
        return MaxWidth::Full;
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        $tabs = [
            'todos' => Tab::make('Todos los clientes')
                ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()->withoutTrashed()),
        ];

        // Solo SuperAdmin debe ver la pestaña de eliminados/restauración.
        if ($user?->isSuperAdmin() === true) {
            $tabs['eliminados'] = Tab::make('Clientes eliminados')
                ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()->onlyTrashed());
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

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->defaultSort('namecommercial')
            ->columns([
                Tables\Columns\TextColumn::make('namecommercial')
                    ->label('Nombre comercial')
                    ->url(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record]))
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->state(fn (Client $record): string => $record->trashed() ? 'Eliminado' : ($record->is_active ? 'Activo' : 'Inactivo'))
                    ->badge()
                    ->color(fn (Client $record): string => $record->trashed() ? 'danger' : ($record->is_active ? 'success' : 'warning')),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creador')
                    ->default('N/A')
                    ->formatStateUsing(fn (?string $state, Client $record): string => $state ?: ($record->createdBy?->email ?: 'N/A'))
                    ->wrap(),
            ]);
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
