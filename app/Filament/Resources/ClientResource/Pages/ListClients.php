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
        return __('client.pages.list_title');
    }

    public function getBreadcrumbs(): array
    {
        // Quitar breadcrumb superior "Cliente > Listado"
        return [];
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        // Reduce separación visual entre sidebar y tabla
        return MaxWidth::Full;
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        $tabs = [
            'todos' => Tab::make(__('client.table.all_clients'))
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
        ];

        // Solo SuperAdmin debe ver la pestaña de eliminados/restauración.
        if ($user?->isSuperAdmin() === true) {
            $tabs['eliminados'] = Tab::make(__('client.table.deleted_clients'))
                ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()->onlyTrashed());
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('common.actions.create'))
                ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->isDistributor() ?? false),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery()
            ->select([
                'clients.id',
                'clients.namecommercial',
                'clients.is_active',
                'clients.deleted_at',
                'clients.owner_id',
                'clients.created_by',
            ])
            ->with(['createdBy:id,name,fullname,email']);

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
                    ->label(__('client.form.commercial_name'))
                    ->url(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record]))
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label(__('common.fields.status'))
                    ->state(fn (Client $record): string => $record->trashed() ? __('common.status.deleted') : ($record->is_active ? __('common.status.active') : __('common.status.inactive')))
                    ->badge()
                    ->color(fn (Client $record): string => $record->trashed() ? 'danger' : ($record->is_active ? 'success' : 'warning')),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('common.fields.creator'))
                    ->default(__('common.placeholders.not_available'))
                    ->formatStateUsing(fn (?string $state, Client $record): string => $state ?: ($record->createdBy?->email ?: __('common.placeholders.not_available')))
                    ->wrap(),
            ]);
    }

    public function getTableHeading(): ?string
    {
        return __('client.pages.list_heading');
    }

    public function getTableEmptyStateHeading(): ?string
    {
        return __('client.table.empty_heading');
    }

    public function getTableEmptyStateDescription(): ?string
    {
        return __('client.table.empty_description');
    }
}
