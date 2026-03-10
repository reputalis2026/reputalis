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

    protected static ?string $navigationLabel = 'Ajustes';

    protected static ?string $modelLabel = 'Sector';

    protected static ?string $pluralModelLabel = 'Sectores';

    protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del sector')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->placeholder('Ej: Farmacia, Herbolario...'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No hay sectores')
            ->emptyStateDescription('Añade sectores para que aparezcan en el formulario de alta de clientes.')
            ->emptyStateIcon('heroicon-o-adjustments-horizontal')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Sector')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clients_count')
                    ->label('Clientes')
                    ->getStateUsing(fn (Sector $record) => $record->clientsCount())
                    ->badge()
                    ->color(fn (Sector $record) => $record->clientsCount() > 0 ? 'gray' : 'success'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->before(function (Sector $record) {
                        if (! $record->canDelete()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede eliminar')
                                ->body('Hay clientes usando este sector. Elimina primero ese sector de los clientes.')
                                ->send();
                            throw new \Exception('No se puede eliminar: hay clientes con este sector.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (! $record->canDelete()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('No se puede eliminar')
                                        ->body("El sector \"{$record->name}\" tiene clientes asignados.")
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
