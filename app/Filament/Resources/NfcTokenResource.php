<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NfcTokenResource\Pages;
use App\Models\NfcToken;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mantener el Resource por compatibilidad con las páginas generadas por Filament,
 * pero ocultarlo completamente del menú y deshabilitar CRUD.
 *
 * La gestión del token NFC debe hacerse desde la ficha de Employee (ClientResource → Empleados).
 */
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
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->label('Token NFC')
                    ->content('La gestión de tokens NFC se realiza desde “Empleados” (1–1 por empleado).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Tokens NFC')
            ->emptyStateDescription('La gestión se realiza desde “Empleados”.');
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
        // Oculto completamente
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

