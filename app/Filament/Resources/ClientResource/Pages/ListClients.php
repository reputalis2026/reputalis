<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public function mount(): void
    {
        parent::mount();

        // El rol cliente no debe ver el listado; redirigir a su dashboard.
        $user = auth()->user();
        if ($user?->isClientOwner() && $user->ownedClient) {
            $this->redirect(ClientResource::getUrl('dashboard', ['record' => $user->ownedClient]));
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

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
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
                'clients.logo',
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
            ->recordUrl(fn (Client $record): string => ClientResource::getUrl('hub', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('namecommercial')
                    ->label(__('client.form.commercial_name'))
                    ->html()
                    ->formatStateUsing(function (?string $state, Client $record): string {
                        $name = e((string) ($state ?: ''));
                        $logoUrl = filled($record->logo)
                            ? \App\Support\ClientImagePaths::publicUrl($record->logo)
                            : null;
                        $initials = e(mb_strtoupper(mb_substr((string) ($state ?: '?'), 0, 1)));

                        $avatar = $logoUrl
                            ? '<img src="'.e($logoUrl).'" alt="" loading="lazy" style="display:block;width:2.55rem;height:2.55rem;flex:0 0 2.55rem;object-fit:cover;border-radius:.35rem;background:#e0f2fe;" />'
                            : '<span style="display:flex;width:2.55rem;height:2.55rem;flex:0 0 2.55rem;align-items:center;justify-content:center;overflow:hidden;border-radius:.35rem;background:#e0f2fe;color:#0369a1;font-size:.8rem;font-weight:700;">'.$initials.'</span>';

                        return '<div style="display:flex;align-items:center;gap:.65rem;min-width:0;"><span style="display:contents;">'.$avatar.'</span><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;">'.$name.'</span></div>';
                    })
                    ->url(fn (Client $record): string => ClientResource::getUrl('hub', ['record' => $record]))
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
