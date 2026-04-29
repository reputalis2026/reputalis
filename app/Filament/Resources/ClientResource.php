<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.clients');
    }

    public static function getModelLabel(): string
    {
        return __('client.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('client.resource.plural_model_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_CLIENTE));
    }

    public static function resolveRecordRouteBinding(int|string $key): ?\Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecordRouteBinding($key)
            ?? app(static::getModel())
                ->resolveRouteBindingQuery(static::getEloquentQuery()->withTrashed(), $key, static::getRecordRouteKeyName())
                ->first();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('client.sections.logo'))
                    ->icon('heroicon-o-photo')
                    ->description(__('client.descriptions.logo'))
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label(__('client.form.logo'))
                            ->image()
                            ->disk('public')
                            ->directory('clients')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight('120')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient)
                    ->collapsed(),

                // Bloque 1: Identificador (solo Create, oculto - autogenerado en CreateClient)
                Forms\Components\Hidden::make('code')
                    ->default('CLIEN000001')
                    ->dehydrated(true),

                // Bloque 2: Datos de Facturación
                Forms\Components\Section::make(__('client.sections.billing'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_inicio_alta')
                            ->label(__('client.form.start_date'))
                            ->default(now()->toDateString())
                            ->disabled()
                            ->dehydrated(true)
                            ->displayFormat('d/m/Y')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
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

                // Bloque 3: Datos del Administrador
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
                                    $component->state($record->owner->admin_email);
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
                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                    ),

                // Bloque 4: Acceso Plataforma (al editar solo se puede cambiar la contraseña, no el usuario)
                Forms\Components\Section::make(__('client.sections.access'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        // Al crear: campo para escribir el usuario de acceso.
                        Forms\Components\TextInput::make('access_email')
                            ->label(__('client.form.platform_user'))
                            ->required()
                            ->unique('users', 'email')
                            ->maxLength(255)
                            ->helperText(__('client.form.platform_user_help'))
                            ->placeholder(__('client.placeholders.platform_user'))
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient),
                        Forms\Components\TextInput::make('access_password')
                            ->label(__('client.form.password'))
                            ->password()
                            ->required(fn ($livewire, $get) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            )
                            ->minLength(8)
                            ->visible(fn ($livewire, $get) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            )
                            ->helperText(__('client.form.password_help')),
                        Forms\Components\TextInput::make('access_password_confirmation')
                            ->label(__('client.form.confirm_password'))
                            ->password()
                            ->required(fn ($livewire, $get) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            )
                            ->same('access_password')
                            ->visible(fn ($livewire, $get) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            ),
                        Forms\Components\Toggle::make('show_password')
                            ->label(__('client.form.show_password'))
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient)
                            ->dehydrated(false)
                            ->reactive()
                            ->default(false),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                    ),
                Forms\Components\Section::make(__('client.sections.status_validity'))
                    ->icon('heroicon-o-calendar-days')
                    ->description(__('client.descriptions.status_validity'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('client.form.active'))
                            ->default(false)
                            ->live()
                            ->disabled(fn () => ! auth()->user()?->isSuperAdmin())
                            ->helperText(__('client.form.active_help'))
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                if ($state) {
                                    $set('activation_duration', 12);
                                    $base = $get('fecha_inicio_alta');
                                    if ($base) {
                                        $set('fecha_fin', \Carbon\Carbon::parse($base)->addMonths(12)->toDateString());
                                    }
                                }
                            }),
                        Forms\Components\Select::make('activation_duration')
                            ->label(__('client.form.activation_duration'))
                            ->options([
                                12 => '12 meses',
                                24 => '24 meses',
                                36 => '36 meses',
                                'custom' => __('client.form.custom_date'),
                            ])
                            ->default(12)
                            ->live()
                            ->dehydrated(false)
                            ->required(fn ($get) => (bool) $get('is_active'))
                            ->helperText(__('client.form.activation_duration_help'))
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                if (is_numeric($state)) {
                                    $base = $get('fecha_inicio_alta');
                                    if ($base) {
                                        $set('fecha_fin', \Carbon\Carbon::parse($base)->addMonths((int) $state)->toDateString());
                                    }
                                }
                            })
                            ->visible(fn ($get) => (bool) $get('is_active')),
                        Forms\Components\DatePicker::make('fecha_fin')
                            ->label(__('client.form.end_date'))
                            ->displayFormat('d/m/Y')
                            ->helperText(__('client.form.end_date_help'))
                            ->required(fn ($get) => (bool) $get('is_active'))
                            ->disabled(fn ($get) => $get('activation_duration') !== 'custom')
                            ->visible(fn ($get) => (bool) $get('is_active')),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                        && auth()->user()?->isSuperAdmin()
                    )
                    ->columns(1),
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
                        TextEntry::make('fecha_fin')->label(__('client.form.end_date'))->date('d/m/Y')->placeholder(__('common.placeholders.empty'))->visible(fn () => auth()->user()?->isSuperAdmin()),
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
                        TextEntry::make('owner.admin_email')->label(__('client.form.admin_email'))->placeholder(__('common.placeholders.empty')),
                    ])
                    ->columns(2),
                InfolistSection::make(__('client.sections.access'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextEntry::make('owner.email')->label(__('client.form.platform_user'))->placeholder(__('common.placeholders.empty')),
                        TextEntry::make('owner.id')->label(__('client.form.password'))->formatStateUsing(fn () => '••••••••')->placeholder(__('common.placeholders.empty')),
                    ])
                    ->columns(2),
                InfolistSection::make(__('client.sections.status'))
                    ->schema([
                        TextEntry::make('is_active')->label(__('client.form.active'))->badge()->formatStateUsing(fn ($state) => $state ? __('common.status.yes') : __('common.status.no'))->color(fn ($state) => $state ? 'success' : 'gray'),
                    ])
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('client.table.empty_heading'))
            ->emptyStateDescription(__('client.table.empty_description'))
            ->emptyStateIcon('heroicon-o-building-storefront')
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
                Tables\Columns\TextColumn::make('last_call_at')
                    ->label(__('client.table.last_call'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('client.table.no_calls_yet')),
                Tables\Columns\TextColumn::make('next_call_at')
                    ->label(__('client.table.next_call'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('common.placeholders.empty'))
                    ->badge()
                    ->color(function ($state, Client $record): string {
                        return $record->next_call_at && $record->next_call_at->isPast() ? 'danger' : 'gray';
                    }),
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
                Tables\Actions\ViewAction::make()
                    ->label(__('common.actions.view')),
                Tables\Actions\EditAction::make()
                    ->label(__('common.actions.edit')),
                Tables\Actions\Action::make('llamadas')
                    ->label(__('client.menu.calls'))
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->url(fn (Client $record): string => static::getUrl('llamadas', ['record' => $record]))
                    ->visible(function () {
                        $user = auth()->user();

                        return $user?->isSuperAdmin() === true || $user?->isDistributor() === true;
                    })
                    ->openUrlInNewTab(false),
                Tables\Actions\Action::make('puntosDeMejora')
                    ->label(__('client.menu.survey'))
                    ->icon('heroicon-o-light-bulb')
                    ->url(fn (Client $record): string => static::getUrl('puntos-de-mejora', ['record' => $record]))
                    ->visible(fn (Client $record): bool => static::canView($record))
                    ->openUrlInNewTab(false),
                Tables\Actions\Action::make('empleados')
                    ->label(__('client.menu.employees'))
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Client $record): string => static::getUrl('empleados', ['record' => $record]))
                    ->visible(fn (Client $record): bool => static::canView($record))
                    ->openUrlInNewTab(false),
                Tables\Actions\DeleteAction::make()
                    ->label(__('common.actions.delete'))
                    ->modalHeading(__('client.actions.delete_client'))
                    ->modalDescription(__('client.actions.delete_description'))
                    ->visible(fn ($record) => ! $record->trashed() && static::canDelete($record)),
                RestoreAction::make()
                    ->label(__('common.actions.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->modalHeading(__('client.actions.restore_heading'))
                    ->modalDescription(__('client.actions.restore_description'))
                    ->visible(fn ($record) => $record->trashed() && static::canRestore($record)),
                Tables\Actions\Action::make('forceDelete')
                    ->label(__('client.actions.delete_database'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('client.actions.force_delete_heading'))
                    ->modalDescription(__('client.actions.force_delete_description'))
                    ->modalSubmitActionLabel(__('common.actions.delete'))
                    ->visible(fn ($record) => $record->trashed() && static::canDelete($record))
                    ->action(function (Client $record): void {
                        $ownerId = $record->owner_id;
                        $clientId = $record->id;
                        \Illuminate\Support\Facades\DB::table('users')->where('id', $ownerId)->delete();
                        \Illuminate\Support\Facades\DB::table('users')->where('client_id', $clientId)->delete();
                        $record->forceDelete();
                    })
                    ->successRedirectUrl(ClientResource::getUrl('index')),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
            'puntos-de-mejora' => Pages\PuntosDeMejora::route('/{record}/puntos-de-mejora'),
            'empleados' => Pages\Empleados::route('/{record}/empleados'),
            'llamadas' => Pages\Llamadas::route('/{record}/llamadas'),
        ];
    }

    /**
     * Subnavegación al ver/editar un cliente: Ver, Editar, Encuesta, Empleados.
     *
     * @return array<\Filament\Navigation\NavigationItem>
     */
    public static function getRecordSubNavigation(\Filament\Resources\Pages\Page $page): array
    {
        $items = [
            Pages\ViewClient::class,
            Pages\PuntosDeMejora::class,
            Pages\Empleados::class,
            Pages\Llamadas::class,
        ];

        // UX: el submenú lateral no incluye "Edit client" porque el flujo de edición
        // se realiza desde el botón superior.
        return $page->generateNavigationItems($items);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isDistributor() || $user?->isClientOwner() || false;
    }

    /**
     * El ítem "Clientes" no se muestra en el menú para el rol cliente;
     * ellos solo ven "Encuesta" (ítem personalizado en AdminPanelProvider).
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isDistributor() || false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isDistributor() || false;
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
            return $record->created_by === $user->id;
        }

        if ($user->isClientOwner()) {
            return $record->owner_id === $user->id;
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
            return $record->created_by === $user->id;
        }

        if ($user->isClientOwner()) {
            return $record->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Solo el superadmin puede eliminar clientes. El distribuidor no puede eliminar.
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canRestore(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Solo el SuperAdmin puede restaurar clientes eliminados.
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
