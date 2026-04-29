<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistributorResource\Pages;
use App\Models\Client;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DistributorResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $slug = 'distribuidores';

    public static function getNavigationLabel(): string
    {
        return __('panel.distributors.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel.distributors.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.distributors.plural_model_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_DISTRIBUIDOR));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('client.sections.logo'))
                    ->icon('heroicon-o-photo')
                    ->description(__('panel.distributors.logo_description'))
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label(__('panel.distributors.logo_label'))
                            ->image()
                            ->disk('public')
                            ->directory('clients')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight('120')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditDistributor),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditDistributor)
                    ->collapsed(),

                Forms\Components\Hidden::make('code')
                    ->default('CLIEN000001')
                    ->dehydrated(true),

                Forms\Components\Section::make(__('client.sections.billing'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_inicio_alta')
                            ->label(__('client.form.start_date'))
                            ->default(now()->toDateString())
                            ->disabled()
                            ->dehydrated(true)
                            ->displayFormat('d/m/Y')
                            ->visible(fn ($livewire) =>
                                $livewire instanceof Pages\CreateDistributor ||
                                $livewire instanceof Pages\EditDistributor
                            ),
                        Forms\Components\TextInput::make('nif')
                            ->label(__('client.form.nif'))
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder(__('client.placeholders.nif')),
                        Forms\Components\TextInput::make('razon_social')
                            ->label(__('client.form.social_name'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('namecommercial')
                            ->label(__('client.form.commercial_name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('calle')
                            ->label(__('client.form.street'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('pais')
                            ->label(__('client.form.country'))
                            ->default('España')
                            ->options([
                                'España' => __('client.countries.spain'),
                                'Portugal' => __('client.countries.portugal'),
                                'Francia' => __('client.countries.france'),
                                'Andorra' => __('client.countries.andorra'),
                                'Otro' => __('client.countries.other'),
                            ])
                            ->searchable()
                            ->native(false),
                        Forms\Components\TextInput::make('codigo_postal')
                            ->label(__('client.form.postal_code'))
                            ->maxLength(20)
                            ->placeholder(__('client.placeholders.postal_code')),
                        Forms\Components\TextInput::make('ciudad')
                            ->label(__('client.form.city'))
                            ->maxLength(100),
                        Forms\Components\Select::make('sector')
                            ->label(__('client.form.sector'))
                            ->options(fn () => \App\Models\Sector::orderBy('sort_order')->orderBy('name')->pluck('name', 'name'))
                            ->default(fn () => \App\Models\Sector::orderBy('sort_order')->orderBy('name')->value('name'))
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('client.sections.admin'))
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('admin_dni')
                            ->label(__('client.form.admin_dni'))
                            ->maxLength(20)
                            ->placeholder(__('client.placeholders.dni'))
                            ->regex('/^[0-9]{8}[A-Za-z]$|^[XYZ][0-9]{7}[A-Za-z]$/')
                            ->validationAttribute('DNI')
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record?->owner?->dni) {
                                    $component->state($record->owner->dni);
                                }
                            }),
                        Forms\Components\TextInput::make('admin_name')
                            ->label(__('client.form.admin_name'))
                            ->maxLength(255)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record?->owner?->fullname) {
                                    $component->state($record->owner->fullname);
                                }
                            }),
                        Forms\Components\TextInput::make('admin_email')
                            ->label(__('client.form.admin_email'))
                            ->email()
                            ->maxLength(255)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record?->owner) {
                                    $component->state($record->owner->admin_email ?? $record->owner->email);
                                }
                            }),
                        Forms\Components\TextInput::make('telefono_negocio')
                            ->label(__('client.form.business_phone'))
                            ->tel()
                            ->maxLength(30)
                            ->placeholder(__('client.placeholders.business_phone')),
                        Forms\Components\TextInput::make('telefono_cliente')
                            ->label(__('client.form.customer_phone'))
                            ->tel()
                            ->maxLength(30)
                            ->placeholder(__('client.placeholders.customer_phone')),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire) =>
                        $livewire instanceof Pages\CreateDistributor ||
                        $livewire instanceof Pages\EditDistributor
                    ),

                Forms\Components\Section::make(__('client.sections.access'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\TextInput::make('access_email')
                            ->label(__('client.form.platform_user'))
                            ->required()
                            ->unique('users', 'email')
                            ->maxLength(255)
                            ->helperText(__('panel.distributors.platform_user_help'))
                            ->placeholder(__('client.placeholders.platform_user'))
                            ->visible(fn ($livewire) => $livewire instanceof Pages\CreateDistributor),
                        Forms\Components\TextInput::make('access_password')
                            ->label(__('client.form.password'))
                            ->password()
                            ->required(fn ($livewire, $get) =>
                                $livewire instanceof Pages\CreateDistributor ||
                                ($livewire instanceof Pages\EditDistributor && $get('show_password'))
                            )
                            ->minLength(8)
                            ->visible(fn ($livewire, $get) =>
                                $livewire instanceof Pages\CreateDistributor ||
                                ($livewire instanceof Pages\EditDistributor && $get('show_password'))
                            )
                            ->helperText(__('client.form.password_help')),
                        Forms\Components\TextInput::make('access_password_confirmation')
                            ->label(__('client.form.confirm_password'))
                            ->password()
                            ->required(fn ($livewire, $get) =>
                                $livewire instanceof Pages\CreateDistributor ||
                                ($livewire instanceof Pages\EditDistributor && $get('show_password'))
                            )
                            ->same('access_password')
                            ->visible(fn ($livewire, $get) =>
                                $livewire instanceof Pages\CreateDistributor ||
                                ($livewire instanceof Pages\EditDistributor && $get('show_password'))
                            ),
                        Forms\Components\Toggle::make('show_password')
                            ->label(__('client.form.show_password'))
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditDistributor)
                            ->dehydrated(false)
                            ->live()
                            ->default(false),
                    ])
                    ->visible(fn ($livewire) =>
                        $livewire instanceof Pages\CreateDistributor ||
                        $livewire instanceof Pages\EditDistributor
                    ),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('panel.distributors.active_label'))
                    ->default(false)
                    ->live()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditDistributor)
                    ->disabled(fn () => ! auth()->user()?->isSuperAdmin())
                    ->helperText(fn () => auth()->user()?->isSuperAdmin() ? __('client.form.active_help') : null),
                Forms\Components\DatePicker::make('fecha_fin')
                    ->label(__('client.form.end_date'))
                    ->displayFormat('d/m/Y')
                    ->helperText(__('panel.distributors.end_date_help'))
                    ->required(fn ($get) => (bool) $get('is_active'))
                    ->visible(fn ($livewire, $get) =>
                        $livewire instanceof Pages\EditDistributor && $get('is_active')
                    ),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make(__('client.sections.billing'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('code')->label(__('common.fields.code')),
                        TextEntry::make('fecha_inicio_alta')->label(__('client.form.start_date'))->date('d/m/Y')->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('fecha_fin')->label(__('client.form.end_date'))->date('d/m/Y')->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('nif')->label(__('client.form.nif'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('razon_social')->label(__('client.form.social_name'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('namecommercial')->label(__('client.form.commercial_name')),
                        TextEntry::make('calle')->label(__('client.form.street'))->placeholder(__('common.placeholders.empty'))->columnSpanFull(),
                        TextEntry::make('pais')->label(__('client.form.country'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('codigo_postal')->label(__('client.form.postal_code'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('ciudad')->label(__('client.form.city'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('sector')->label(__('client.form.sector'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('telefono_negocio')->label(__('client.form.business_phone'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('telefono_cliente')->label(__('client.form.customer_phone'))->placeholder(__('common.placeholders.empty')),
                    ])
                    ->columns(2),
                InfolistSection::make(__('client.sections.admin'))
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextEntry::make('owner.fullname')->label(__('client.form.admin_name'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('owner.dni')->label(__('client.form.admin_dni'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('owner.admin_email')->label(__('client.form.admin_email'))->placeholder(__('common.placeholders.empty'))->formatStateUsing(fn ($state, $record) => $state ?? $record?->owner?->email ?? __('common.placeholders.empty')),
                    ])
                    ->columns(2),
                InfolistSection::make(__('client.sections.access'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextEntry::make('owner.email')->label(__('client.form.platform_user'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('owner.id')->label(__('client.form.password'))->formatStateUsing(fn () => '••••••')->placeholder(__('common.placeholders.empty')),
                    ])
                    ->columns(2),
                InfolistSection::make(__('client.sections.status'))
                    ->schema([
                        TextEntry::make('is_active')->label(__('common.fields.active'))->badge()->formatStateUsing(fn ($state) => $state ? __('common.status.yes') : __('common.status.no'))->color(fn ($state) => $state ? 'success' : 'gray'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('panel.distributors.empty_heading'))
            ->emptyStateDescription(__('panel.distributors.empty_description'))
            ->emptyStateIcon('heroicon-o-truck')
            ->columns([
                Tables\Columns\TextColumn::make('namecommercial')
                    ->label(__('client.form.commercial_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('common.fields.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.fullname')
                    ->label(__('common.fields.creator'))
                    ->formatStateUsing(fn ($state, $record) => $record->createdBy?->fullname ?: $record->createdBy?->name ?: $record->createdBy?->email ?: __('common.placeholders.empty'))
                    ->searchable(query: function ($query, $search) {
                        $search = addcslashes($search, '%_');
                        return $query->whereHas('createdBy', fn ($q) => $q->where('fullname', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                    })
                    ->sortable()
                    ->placeholder(__('common.placeholders.empty')),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('common.fields.code'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('common.fields.status'))
                    ->placeholder(__('client.table.all'))
                    ->trueLabel(__('client.table.active'))
                    ->falseLabel(__('client.table.inactive')),
                Tables\Filters\SelectFilter::make('created_by')
                    ->label(__('common.fields.creator'))
                    ->relationship('createdBy', 'fullname')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->fullname ?: $record->email),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('common.actions.view')),
                Tables\Actions\EditAction::make()->label(__('common.actions.edit')),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistributors::route('/'),
            'create' => Pages\CreateDistributor::route('/create'),
            'view' => Pages\ViewDistributor::route('/{record}'),
            'edit' => Pages\EditDistributor::route('/{record}/edit'),
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
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
