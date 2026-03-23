<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?string $modelLabel = 'Empleado';

    protected static ?string $pluralModelLabel = 'Empleados';

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $isDistributor = $user?->isDistributor() ?? false;
        $isClientOwner = $user?->isClientOwner() ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make('Datos del empleado')
                    ->schema([
                        Forms\Components\Hidden::make('client_id')
                            ->default(fn () => request()->query('client_id'))
                            ->required(),
                        Forms\Components\Placeholder::make('client_display')
                            ->label('Nombre del cliente')
                            ->content(fn ($get): string => (string) (\App\Models\Client::find($get('client_id'))?->namecommercial ?? '—'))
                            ->visible(fn ($get): bool => filled($get('client_id'))),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('alias')
                            ->label('Alias / identificador')
                            ->maxLength(100)
                            ->helperText('Identificador corto para usar en encuestas o como código.'),
                        Forms\Components\FileUpload::make('photo')
                            ->label('Foto')
                            ->image()
                            ->disk('public')
                            ->directory('employees')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight(120)
                            ->maxSize(1024),
                        Forms\Components\TextInput::make('position')
                            ->label('Puesto')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('Token NFC')
                    ->icon('heroicon-o-credit-card')
                    ->description('Token NFC 1–1 por empleado. No editable.')
                    ->schema([
                        Forms\Components\TextInput::make('nfc_token')
                            ->label('Token NFC')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, ?\App\Models\Employee $record): void {
                                if ($record?->nfcTokens?->token) {
                                    $component->state($record->nfcTokens->token);
                                } else {
                                    $component->state('Se generará al guardar');
                                }
                            }),
                        Forms\Components\TextInput::make('nfc_survey_url')
                            ->label('Copiar enlace de encuesta')
                            ->disabled()
                            ->dehydrated(false)
                            // En esta versión de Filament no existe TextInput::copyable().
                            // El copiado se realiza con un botón JS debajo.
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, ?\App\Models\Employee $record): void {
                                if ($record?->nfcTokens?->token) {
                                    $component->state(url('/survey/nfc/' . $record->nfcTokens->token));
                                } else {
                                    $component->state('—');
                                }
                            }),
                        Forms\Components\Placeholder::make('copy_nfc_survey_url')
                            ->content(function (? \App\Models\Employee $record): ?\Illuminate\Support\HtmlString {
                                $token = $record?->nfcTokens?->token;
                                if (! $token) {
                                    return null;
                                }

                                $url = url('/survey/nfc/' . $token);

                                $onclick = 'navigator.clipboard.writeText(' . json_encode($url) . ').then(() => {' .
                                    'window.dispatchEvent(new CustomEvent(\'notificationSent\', { detail: { notification: { title: \'Enlace de encuesta copiado\', status: \'success\' } } }));' .
                                '});';

                                return new \Illuminate\Support\HtmlString(
                                    '<x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document" outlined ' .
                                    'onclick="' . $onclick . '"' .
                                    '>Copiar enlace</x-filament::button>'
                                );
                            }),
                    ])
                    ->columns(1),
            ]);
    }

    protected static function scopeClientQuery(Builder $query): Builder
    {
        $user = auth()->user();
        if ($user?->isSuperAdmin()) {
            return $query->orderBy('namecommercial');
        }
        if ($user?->isDistributor()) {
            return $query->where('created_by', $user->id)->orderBy('namecommercial');
        }
        if ($user?->isClientOwner() && $user->ownedClient) {
            return $query->where('id', $user->ownedClient->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        return $table
            ->emptyStateHeading('No hay empleados')
            ->emptyStateDescription('Añade el primer empleado para comenzar.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->columns([
                Tables\Columns\TextColumn::make('client.namecommercial')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => $record->client ? $record->client->namecommercial . ' (' . $record->client->code . ')' : '-')
                    ->searchable(['clients.namecommercial', 'clients.code'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alias')
                    ->label('Alias')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Puesto')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(
                $isSuperAdmin
                    ? [
                        Tables\Filters\SelectFilter::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'namecommercial')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->namecommercial . ' (' . $record->code . ')'),
                    ]
                    : []
            )
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions(
                $isSuperAdmin
                    ? [
                        Tables\Actions\BulkActionGroup::make([
                            Tables\Actions\DeleteBulkAction::make()
                                ->label('Eliminar seleccionados')
                                ->requiresConfirmation()
                                ->modalHeading('Eliminar empleados seleccionados')
                                ->modalDescription('¿Estás seguro de que deseas eliminar estos empleados? Esta acción no se puede deshacer.')
                                ->modalSubmitActionLabel('Eliminar')
                                ->modalCancelActionLabel('Cancelar'),
                        ]),
                    ]
                    : []
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isDistributor() || $user?->isClientOwner() || false;
    }

    /**
     * Oculto en el menú: la gestión se hace desde Cliente → Empleados (y rol cliente ve "Empleados" en su menú lateral).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDistributor()) {
            return true;
        }
        if ($user->isClientOwner()) {
            return false;
        }

        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDistributor()) {
            return $record->client && $record->client->created_by === $user->id;
        }

        return false;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDistributor()) {
            return $record->client && $record->client->created_by === $user->id;
        }
        if ($user->isClientOwner()) {
            return $record->client_id === $user->ownedClient?->id;
        }

        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDistributor()) {
            return $record->client && $record->client->created_by === $user->id;
        }

        return false;
    }

    public static function canDeleteAny(): bool
    {
        return static::canCreate();
    }
}
