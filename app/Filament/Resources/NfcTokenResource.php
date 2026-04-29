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
    public static function getNavigationLabel(): string
    {
        return __('panel.nfc_tokens.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel.nfc_tokens.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.nfc_tokens.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.navigation_groups.configuration');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->label(__('panel.nfc_tokens.model_label'))
                    ->content(__('panel.nfc_tokens.management_note')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('panel.nfc_tokens.empty_heading'))
            ->emptyStateDescription(__('panel.nfc_tokens.empty_description'));
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

