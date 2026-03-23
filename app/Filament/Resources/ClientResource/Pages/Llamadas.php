<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\ClientCall;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Concerns\InteractsWithTable as TablesInteractsWithTable;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Llamadas extends Page implements HasTable
{
    use InteractsWithRecord;
    use TablesInteractsWithTable;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.llamadas';

    protected static ?string $navigationLabel = 'Llamadas';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        $client = $this->getRecord();

        if (! $user || ! $client) {
            abort(403);
        }

        if ($user->isClientOwner()) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isDistributor()) {
            abort_unless($client->created_by === $user->id, 403);

            return;
        }

        abort(403);
    }

    public function canManageCalls(): bool
    {
        $user = auth()->user();
        $client = $this->getRecord();

        if (! $user || ! $client) {
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

    public function getTitle(): string
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return 'Llamadas';
    }

    public function getBreadcrumb(): string
    {
        return 'Llamadas';
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;
        $user = auth()->user();

        if (! $user || ! $record) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isDistributor()) {
            return $record->created_by === $user->id;
        }

        return false;
    }

    /**
     * Acciones tipo "botón" en cabecera.
     */
    protected function getHeaderActions(): array
    {
        if (! $this->canManageCalls()) {
            return [];
        }

        return [
            HeaderAction::make('registrar_llamada')
                ->label('Registrar llamada de hoy')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->modalHeading('Observaciones de la llamada')
                ->form([
                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(5)
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    /** @var Client $client */
                    $client = $this->getRecord();

                    $now = now();
                    $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

                    ClientCall::create([
                        'client_id' => $client->id,
                        'called_at' => $now,
                        'notes' => $notes,
                    ]);

                    $client->last_call_at = $now;
                    $client->next_call_at = $now->copy()->addDays(30);
                    $client->save();

                    Notification::make()
                        ->success()
                        ->title('Llamada registrada')
                        ->send();
                }),

            HeaderAction::make('programar_proxima_llamada')
                ->label('Programar próxima llamada')
                ->icon('heroicon-o-calendar-days')
                ->modalHeading('Programar próxima llamada')
                ->form([
                    DateTimePicker::make('next_call_at')
                        ->label('Fecha y hora')
                        ->required()
                        ->default(fn () => $this->getRecord()->next_call_at ?? now()->addDays(30)),
                ])
                ->action(function (array $data): void {
                    /** @var Client $client */
                    $client = $this->getRecord();

                    $client->next_call_at = $data['next_call_at'];
                    $client->save();

                    Notification::make()
                        ->success()
                        ->title('Próxima llamada programada')
                        ->send();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return ClientCall::query()
            ->where('client_id', $client->id)
            ->orderByDesc('called_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('called_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Observaciones')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('editar_notas')
                    ->label('Editar notas')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar observaciones de la llamada')
                    ->form([
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(5)
                            ->default(fn (ClientCall $record) => $record->notes),
                    ])
                    ->action(function (ClientCall $record, array $data): void {
                        $record->notes = isset($data['notes']) ? trim((string) $data['notes']) : null;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Notas actualizadas')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Sin llamadas registradas')
            ->emptyStateDescription('Registra tu primera llamada para ver el historial aquí.')
            ->emptyStateIcon('heroicon-o-phone-arrow-up-right');
    }

    public static function getNavigationUrl(array $parameters = []): string
    {
        $record = $parameters['record'] ?? null;

        return $record
            ? ClientResource::getUrl('llamadas', ['record' => $record])
            : ClientResource::getUrl('index');
    }
}

