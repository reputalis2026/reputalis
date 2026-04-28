<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CsatSurveyResource\Pages;
use App\Models\CsatSurvey;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CsatSurveyResource extends Resource
{
    protected static ?string $model = CsatSurvey::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Encuestas CSAT';

    protected static ?string $modelLabel = 'Encuesta CSAT';

    protected static ?string $pluralModelLabel = 'Encuestas CSAT';

    protected static ?string $navigationGroup = 'Encuestas';

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        return $table
            ->emptyStateHeading('No hay encuestas')
            ->emptyStateDescription('Las encuestas enviadas desde los dispositivos aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->columns([
                Tables\Columns\TextColumn::make('client.namecommercial')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => $record->client ? $record->client->namecommercial.' ('.$record->client->code.')' : '-')
                    ->searchable(['clients.namecommercial', 'clients.code'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->placeholder('Sin asignar')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Puntuación')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state == 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('improvementReason.code')
                    ->label('Motivo mejora')
                    ->state(function ($record) {
                        return $record->improvementOption?->labelForLocale($record->locale_used)
                            ?? $record->improvementReason?->code
                            ?? 'Sin motivo';
                    })
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('locale_used')
                    ->label('Idioma')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_hash')
                    ->label('Dispositivo')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 8) : '-')
                    ->copyable()
                    ->copyMessage('Copiado')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Europe/Madrid')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('score')
                    ->label('Puntuación')
                    ->options([
                        '1-2' => '1–2 (bajo)',
                        '3' => '3 (neutral)',
                        '4-5' => '4–5 (alto)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return match ($value) {
                            '1-2' => $query->whereIn('score', [1, 2]),
                            '3' => $query->where('score', 3),
                            '4-5' => $query->whereIn('score', [4, 5]),
                            default => $query,
                        };
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
                ...($isSuperAdmin ? [
                    Tables\Filters\SelectFilter::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'namecommercial')
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->namecommercial.' ('.$record->code.')'),
                ] : []),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Encuesta')
                    ->schema([
                        TextEntry::make('score')
                            ->label('Puntuación')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 4 => 'success',
                                $state == 3 => 'warning',
                                default => 'danger',
                            })
                            ->size('lg'),
                        TextEntry::make('client')
                            ->label('Cliente')
                            ->formatStateUsing(fn ($state) => $state ? $state->namecommercial.' ('.$state->code.')' : '-'),
                        TextEntry::make('employee.name')
                            ->label('Empleado')
                            ->placeholder('Sin asignar'),
                        TextEntry::make('improvementReason')
                            ->label('Motivo de mejora')
                            ->formatStateUsing(fn ($state, $record) => $record->improvementOption?->labelForLocale($record->locale_used)
                                ?? $record->improvementReason?->code
                                ?? 'Sin motivo'),
                        TextEntry::make('locale_used')
                            ->label('Idioma')
                            ->placeholder('-'),
                        TextEntry::make('device_hash')
                            ->label('Hash dispositivo')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Fecha y hora')
                            ->dateTime('d/m/Y H:i')
                            ->timezone('Europe/Madrid'),
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
            'index' => Pages\ListCsatSurveys::route('/'),
            'view' => Pages\ViewCsatSurvey::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isDistributor() || $user->isClientOwner();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
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
        if ($user->isClientOwner()) {
            return $record->client_id === $user->ownedClient?->id;
        }
        if ($user->isDistributor()) {
            $client = $record->relationLoaded('client') ? $record->client : $record->client()->first();

            return $client && $client->created_by === $user->id;
        }

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
