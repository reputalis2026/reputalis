<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientCalls extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    protected static string $view = 'filament.pages.client-calls';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.calls');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('client.menu.clients');
    }

    public function getTitle(): string
    {
        return __('client.calls.pending_title');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() === true || $user?->isDistributor() === true;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() === true || $user?->isDistributor() === true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('namecommercial')
                    ->label(__('common.fields.client'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_call_at')
                    ->label(__('client.table.last_call'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('client.table.no_calls_yet')),
                TextColumn::make('next_call_at')
                    ->label(__('client.table.next_call'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('common.placeholders.empty'))
                    ->badge()
                    ->color(function ($state, Client $record): string {
                        if (! $record->next_call_at) {
                            return 'gray';
                        }

                        return $record->next_call_at->isPast() ? 'danger' : 'gray';
                    }),
                TextColumn::make('next_call_status')
                    ->label(__('calls.status'))
                    ->state(function (Client $record): ?string {
                        if (! $record->next_call_at || ! $record->next_call_at->isPast()) {
                            return null;
                        }

                        return __('calls.overdue');
                    })
                    ->badge()
                    ->color('danger')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('ver')
                    ->label(__('common.actions.view'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Client $record): string => ClientResource::getUrl('llamadas', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->emptyStateHeading(__('client.calls.pending_empty_heading'))
            ->emptyStateDescription(__('client.calls.pending_empty_description'))
            ->emptyStateIcon('heroicon-o-phone-arrow-up-right');
    }

    protected function getTableQuery(): Builder
    {
        $query = Client::query()
            ->withoutTrashed()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_CLIENTE))
            ->with(['owner', 'calls' => fn ($q) => $q->latest('called_at')])
            ->select(['id', 'namecommercial', 'last_call_at', 'next_call_at', 'created_by']);

        $user = auth()->user();
        if ($user?->isDistributor() === true) {
            $query->where('created_by', $user->id);
        }

        // Los que no tengan próxima llamada al final.
        return $query
            ->orderByRaw('next_call_at IS NULL, next_call_at ASC')
            ->orderBy('namecommercial');
    }
}
