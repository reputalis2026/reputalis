<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectorResource\Pages;
use App\Models\Sector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SectorResource extends Resource
{
    protected static ?string $model = Sector::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function getNavigationLabel(): string
    {
        return __('panel.sectors.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel.sectors.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.sectors.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.navigation_groups.configuration');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('panel.sectors.name'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->placeholder(__('panel.sectors.placeholder')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('panel.sectors.empty_heading'))
            ->emptyStateDescription(__('panel.sectors.empty_description'))
            ->emptyStateIcon('heroicon-o-adjustments-horizontal')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('common.fields.sector'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clients_count')
                    ->label(__('client.menu.clients'))
                    ->getStateUsing(fn (Sector $record) => $record->clientsCount())
                    ->badge()
                    ->color(fn (Sector $record) => $record->clientsCount() > 0 ? 'gray' : 'success'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('common.actions.edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('common.actions.delete'))
                    ->before(function (Sector $record) {
                        if (! $record->canDelete()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title(__('panel.sectors.cannot_delete_title'))
                                ->body(__('panel.sectors.cannot_delete_body'))
                                ->send();
                            throw new \Exception(__('panel.sectors.cannot_delete_exception'));
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('common.actions.delete_selected'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (! $record->canDelete()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title(__('panel.sectors.cannot_delete_title'))
                                        ->body(__('panel.sectors.cannot_delete_assigned', ['sector' => $record->name]))
                                        ->send();
                                    return;
                                }
                            }
                            $records->each->delete();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSectors::route('/'),
            'create' => Pages\CreateSector::route('/create'),
            'edit' => Pages\EditSector::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
