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

    public static function getNavigationLabel(): string
    {
        return __('survey.resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('survey.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('survey.resource.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('survey.resource.navigation_group');
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        return $table
            ->emptyStateHeading(__('survey.resource.empty_heading'))
            ->emptyStateDescription(__('survey.resource.empty_description'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->columns([
                Tables\Columns\TextColumn::make('client.namecommercial')
                    ->label(__('common.fields.client'))
                    ->formatStateUsing(fn ($record) => $record->client ? $record->client->namecommercial.' ('.$record->client->code.')' : __('common.placeholders.empty'))
                    ->searchable(['clients.namecommercial', 'clients.code'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label(__('common.fields.employee'))
                    ->placeholder(__('survey.resource.unassigned_employee'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label(__('common.fields.score'))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state == 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('improvementReason.code')
                    ->label(__('survey.resource.improvement_reason_short'))
                    ->state(function ($record) {
                        return $record->improvementOption?->labelForLocale($record->locale_used)
                            ?? $record->improvementReason?->code
                            ?? __('survey.resource.no_reason');
                    })
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('locale_used')
                    ->label(__('common.fields.language'))
                    ->placeholder(__('common.placeholders.empty'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_hash')
                    ->label(__('common.fields.device'))
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 8) : __('common.placeholders.empty'))
                    ->copyable()
                    ->copyMessage(__('common.messages.copied'))
                    ->placeholder(__('common.placeholders.empty')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('common.fields.date'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Europe/Madrid')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('score')
                    ->label(__('common.fields.score'))
                    ->options([
                        '1-2' => __('survey.resource.filters.low'),
                        '3' => __('survey.resource.filters.neutral'),
                        '4-5' => __('survey.resource.filters.high'),
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
                            ->label(__('survey.resource.filters.from')),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label(__('survey.resource.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
                ...($isSuperAdmin ? [
                    Tables\Filters\SelectFilter::make('client_id')
                        ->label(__('common.fields.client'))
                        ->relationship('client', 'namecommercial')
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->namecommercial.' ('.$record->code.')'),
                ] : []),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('common.actions.view')),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make(__('survey.resource.section'))
                    ->schema([
                        TextEntry::make('score')
                            ->label(__('common.fields.score'))
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 4 => 'success',
                                $state == 3 => 'warning',
                                default => 'danger',
                            })
                            ->size('lg'),
                        TextEntry::make('client')
                            ->label(__('common.fields.client'))
                            ->formatStateUsing(fn ($state) => $state ? $state->namecommercial.' ('.$state->code.')' : __('common.placeholders.empty')),
                        TextEntry::make('employee.name')
                            ->label(__('common.fields.employee'))
                            ->placeholder(__('survey.resource.unassigned_employee')),
                        TextEntry::make('improvementReason')
                            ->label(__('survey.resource.improvement_reason'))
                            ->formatStateUsing(fn ($state, $record) => $record->improvementOption?->labelForLocale($record->locale_used)
                                ?? $record->improvementReason?->code
                                ?? __('survey.resource.no_reason')),
                        TextEntry::make('locale_used')
                            ->label(__('common.fields.language'))
                            ->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('device_hash')
                            ->label(__('survey.resource.device_hash'))
                            ->copyable()
                            ->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('created_at')
                            ->label(__('common.fields.date_time'))
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
