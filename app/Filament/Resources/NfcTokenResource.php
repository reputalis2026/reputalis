<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NfcTokenResource\Pages;
use App\Models\NfcToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NfcTokenResource extends Resource
{
    protected static ?string $model = NfcToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Tokens NFC';

    protected static ?string $modelLabel = 'Token NFC';

    protected static ?string $pluralModelLabel = 'Tokens NFC';

    protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $isClientOwner = $user?->isClientOwner() ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make('Datos del token NFC')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship(
                                'client',
                                'namecommercial',
                                fn (Builder $query) => $query->orderBy('namecommercial')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->namecommercial . ' (' . $record->code . ')')
                            ->searchable(['namecommercial', 'code'])
                            ->preload()
                            ->required()
                            ->default(fn () => $isClientOwner ? $user?->ownedClient?->id : null)
                            ->disabled(fn () => $isClientOwner)
                            ->dehydrated(true),
                        Forms\Components\Select::make('employee_id')
                            ->label('Empleado asignado')
                            ->relationship(
                                'employee',
                                'name',
                                fn (Builder $query, Get $get) => $get('client_id')
                                    ? $query->where('client_id', $get('client_id'))
                                    : $query->whereRaw('1 = 0')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable(['name'])
                            ->preload()
                            ->nullable()
                            ->placeholder('Sin empleado asignado'),
                        Forms\Components\TextInput::make('token')
                            ->label('Token (UID del chip)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Identificador único del chip NFC'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        return $table
            ->emptyStateHeading('No hay tokens NFC')
            ->emptyStateDescription('Registra el primer chip o token NFC para encuestas.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->columns([
                Tables\Columns\TextColumn::make('client.namecommercial')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => $record->client ? $record->client->namecommercial . ' (' . $record->client->code . ')' : '-')
                    ->searchable(['clients.namecommercial', 'clients.code'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->placeholder('Sin asignar')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('token')
                    ->label('Token')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Token copiado')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
                ...($isSuperAdmin ? [
                    Tables\Filters\SelectFilter::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'namecommercial')
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->namecommercial . ' (' . $record->code . ')'),
                ] : []),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar tokens NFC seleccionados')
                        ->modalDescription('¿Estás seguro de que deseas eliminar estos tokens?')
                        ->modalSubmitActionLabel('Eliminar')
                        ->modalCancelActionLabel('Cancelar'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNfcTokens::route('/'),
            'create' => Pages\CreateNfcToken::route('/create'),
            'edit' => Pages\EditNfcToken::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClientOwner()) {
            return (bool) $user->ownedClient?->id;
        }
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClientOwner()) {
            return $record->client_id === $user->ownedClient?->id;
        }
        return false;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClientOwner()) {
            return $record->client_id === $user->ownedClient?->id;
        }
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClientOwner()) {
            return $record->client_id === $user->ownedClient?->id;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
