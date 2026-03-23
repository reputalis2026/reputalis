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
use Filament\Tables\Table;
use Filament\Tables\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_CLIENTE));
    }

    public static function resolveRecordRouteBinding(int | string $key): ?\Illuminate\Database\Eloquent\Model
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
                Forms\Components\Section::make('Logo')
                    ->icon('heroicon-o-photo')
                    ->description('Se mostrará en la esquina superior izquierda del panel cuando el usuario del cliente inicie sesión.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo del cliente')
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
                Forms\Components\Section::make('Datos de Facturación')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_inicio_alta')
                            ->label('Fecha de inicio de alta')
                            ->default(now()->toDateString())
                            ->disabled()
                            ->dehydrated(true)
                            ->displayFormat('d/m/Y')
                            ->visible(fn ($livewire) =>
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                            ),
                        Forms\Components\TextInput::make('nif')
                            ->label('NIF')
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Ej: B12345678'),
                        Forms\Components\TextInput::make('razon_social')
                            ->label('Razón Social')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('namecommercial')
                            ->label('Nombre Comercial')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('calle')
                            ->label('Calle')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('pais')
                            ->label('País')
                            ->default('España')
                            ->options([
                                'España' => 'España',
                                'Portugal' => 'Portugal',
                                'Francia' => 'Francia',
                                'Andorra' => 'Andorra',
                                'Otro' => 'Otro',
                            ])
                            ->searchable()
                            ->native(false),
                        Forms\Components\TextInput::make('codigo_postal')
                            ->label('Código Postal')
                            ->maxLength(20)
                            ->placeholder('Ej: 28001'),
                        Forms\Components\TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->maxLength(100),
                        Forms\Components\Select::make('sector')
                            ->label('Sector')
                            ->options(fn () => \App\Models\Sector::orderBy('sort_order')->orderBy('name')->pluck('name', 'name'))
                            ->default(fn () => \App\Models\Sector::orderBy('sort_order')->orderBy('name')->value('name'))
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns(2),

                // Bloque 3: Datos del Administrador
                Forms\Components\Section::make('Datos del Administrador')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('admin_dni')
                            ->label('DNI Admin')
                            ->maxLength(20)
                            ->placeholder('Ej: 12345678A')
                            ->regex('/^[0-9]{8}[A-Za-z]$|^[XYZ][0-9]{7}[A-Za-z]$/')
                            ->validationAttribute('DNI')
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record?->owner?->dni) {
                                    $component->state($record->owner->dni);
                                }
                            }),
                        Forms\Components\TextInput::make('admin_name')
                            ->label('Nombre y Apellidos')
                            ->maxLength(255)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record?->owner?->fullname) {
                                    $component->state($record->owner->fullname);
                                }
                            }),
                        Forms\Components\TextInput::make('admin_email')
                            ->label('Correo Admin')
                            ->email()
                            ->maxLength(255)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record?->owner) {
                                    $component->state($record->owner->admin_email ?? $record->owner->email);
                                }
                            }),
                        Forms\Components\TextInput::make('telefono_negocio')
                            ->label('Teléfono Negocio')
                            ->tel()
                            ->maxLength(30)
                            ->placeholder('Ej: 912345678'),
                        Forms\Components\TextInput::make('telefono_cliente')
                            ->label('Teléfono Cliente')
                            ->tel()
                            ->maxLength(30)
                            ->placeholder('Ej: 612345678'),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire) =>
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                    ),

                // Bloque 4: Acceso Plataforma (al editar solo se puede cambiar la contraseña, no el usuario)
                Forms\Components\Section::make('Acceso a Plataforma')
                    ->icon('heroicon-o-key')
                    ->schema([
                        // Al crear: campo para escribir el usuario de acceso.
                        Forms\Components\TextInput::make('access_email')
                            ->label('Usuario')
                            ->required()
                            ->unique('users', 'email')
                            ->maxLength(255)
                            ->helperText('Usuario o correo para acceso a la plataforma (puede ser distinto del Correo Admin).')
                            ->placeholder('Usuario o email')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient),
                        Forms\Components\TextInput::make('access_password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn ($livewire, $get) =>
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            )
                            ->minLength(8)
                            ->visible(fn ($livewire, $get) =>
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            )
                            ->helperText('Mínimo 8 caracteres'),
                        Forms\Components\TextInput::make('access_password_confirmation')
                            ->label('Confirmar')
                            ->password()
                            ->required(fn ($livewire, $get) =>
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            )
                            ->same('access_password')
                            ->visible(fn ($livewire, $get) =>
                                $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                                ($livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient && $get('show_password'))
                            ),
                        Forms\Components\Toggle::make('show_password')
                            ->label('Cambiar contraseña')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient)
                            ->dehydrated(false)
                            ->reactive()
                            ->default(false),
                    ])
                    ->visible(fn ($livewire) =>
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\CreateClient ||
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                    ),
                Forms\Components\Section::make('Estado y vigencia')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Solo el SuperAdmin puede activar o desactivar el cliente y fijar la fecha de expiración.')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Cliente activo')
                            ->default(false)
                            ->live()
                            ->disabled(fn () => ! auth()->user()?->isSuperAdmin())
                            ->helperText('Solo superadmin puede activar/desactivar.')
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
                            ->label('Duración de activación')
                            ->options([
                                12 => '12 meses',
                                24 => '24 meses',
                                36 => '36 meses',
                                'custom' => 'Otra fecha',
                            ])
                            ->default(12)
                            ->live()
                            ->dehydrated(false)
                            ->required(fn ($get) => (bool) $get('is_active'))
                            ->helperText('Obligatoria al activar el cliente. Elija un plazo o "Otra fecha" para indicar manualmente.')
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
                            ->label('Fecha de fin (expiración)')
                            ->displayFormat('d/m/Y')
                            ->helperText('Se calcula automáticamente con 12/24/36 meses, o elíjala manualmente con "Otra fecha".')
                            ->required(fn ($get) => (bool) $get('is_active'))
                            ->disabled(fn ($get) => $get('activation_duration') !== 'custom')
                            ->visible(fn ($get) => (bool) $get('is_active')),
                    ])
                    ->visible(fn ($livewire) =>
                        $livewire instanceof \App\Filament\Resources\ClientResource\Pages\EditClient
                        && auth()->user()?->isSuperAdmin()
                    )
                    ->columns(1),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Datos de Facturación')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('code')->label('Código'),
                        TextEntry::make('fecha_inicio_alta')->label('Fecha de inicio de alta')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('fecha_fin')->label('Fecha de fin (expiración)')->date('d/m/Y')->placeholder('—')->visible(fn () => auth()->user()?->isSuperAdmin()),
                        TextEntry::make('nif')->label('NIF')->placeholder('—'),
                        TextEntry::make('razon_social')->label('Razón Social')->placeholder('—'),
                        TextEntry::make('namecommercial')->label('Nombre Comercial'),
                        TextEntry::make('calle')->label('Calle')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('pais')->label('País')->placeholder('—'),
                        TextEntry::make('codigo_postal')->label('Código Postal')->placeholder('—'),
                        TextEntry::make('ciudad')->label('Ciudad')->placeholder('—'),
                        TextEntry::make('sector')->label('Sector')->placeholder('—'),
                        TextEntry::make('telefono_negocio')->label('Teléfono Negocio')->placeholder('—'),
                        TextEntry::make('telefono_cliente')->label('Teléfono Cliente')->placeholder('—'),
                    ])
                    ->columns(2),
                InfolistSection::make('Datos del Administrador')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextEntry::make('owner.fullname')->label('Nombre y Apellidos')->placeholder('—'),
                        TextEntry::make('owner.dni')->label('DNI Admin')->placeholder('—'),
                        TextEntry::make('owner.admin_email')->label('Correo Admin')->placeholder('—')->formatStateUsing(fn ($state, $record) => $state ?? $record?->owner?->email ?? '—'),
                    ])
                    ->columns(2),
                InfolistSection::make('Acceso a Plataforma')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextEntry::make('owner.email')->label('Usuario de acceso')->placeholder('—'),
                        TextEntry::make('owner.id')->label('Contraseña')->formatStateUsing(fn () => '••••••••')->placeholder('—'),
                    ])
                    ->columns(2),
                InfolistSection::make('Estado')
                    ->schema([
                        TextEntry::make('is_active')->label('Cliente activo')->badge()->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')->color(fn ($state) => $state ? 'success' : 'gray'),
                    ])
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No hay clientes')
            ->emptyStateDescription('Crea tu primer cliente para comenzar.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->columns([
                Tables\Columns\TextColumn::make('namecommercial')
                    ->label('Nombre Comercial')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_call_at')
                    ->label('Última llamada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Sin llamadas aún'),
                Tables\Columns\TextColumn::make('next_call_at')
                    ->label('Próxima llamada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->badge()
                    ->color(function ($state, Client $record): string {
                        return $record->next_call_at && $record->next_call_at->isPast() ? 'danger' : 'gray';
                    }),
                Tables\Columns\TextColumn::make('createdBy.fullname')
                    ->label('Creador')
                    ->formatStateUsing(fn ($state, $record) => $record->createdBy?->fullname ?: $record->createdBy?->name ?: $record->createdBy?->email ?: '—')
                    ->searchable(query: function ($query, $search) {
                        $search = addcslashes($search, '%_');
                        return $query->whereHas('createdBy', fn ($q) => $q->where('fullname', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                    })
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Creador')
                    ->relationship('createdBy', 'fullname')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->fullname ?: $record->email),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('llamadas')
                    ->label('Llamadas')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->url(fn (Client $record): string => static::getUrl('llamadas', ['record' => $record]))
                    ->visible(function () {
                        $user = auth()->user();

                        return $user?->isSuperAdmin() === true || $user?->isDistributor() === true;
                    })
                    ->openUrlInNewTab(false),
                Tables\Actions\Action::make('puntosDeMejora')
                    ->label('Puntos de mejora')
                    ->icon('heroicon-o-light-bulb')
                    ->url(fn (Client $record): string => static::getUrl('puntos-de-mejora', ['record' => $record]))
                    ->visible(fn (Client $record): bool => static::canView($record))
                    ->openUrlInNewTab(false),
                Tables\Actions\Action::make('empleados')
                    ->label('Empleados')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Client $record): string => static::getUrl('empleados', ['record' => $record]))
                    ->visible(fn (Client $record): bool => static::canView($record))
                    ->openUrlInNewTab(false),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Eliminar cliente')
                    ->modalDescription('El cliente pasará a la pestaña "Clientes eliminados". No se borra de la base de datos.')
                    ->visible(fn ($record) => ! $record->trashed() && static::canDelete($record)),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->modalHeading('Restaurar cliente')
                    ->modalDescription('¿Restaurar este cliente? Se reactivará completamente.')
                    ->visible(fn ($record) => $record->trashed() && static::canRestore($record)),
                Tables\Actions\Action::make('forceDelete')
                    ->label('Eliminar de la base de datos')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar definitivamente')
                    ->modalDescription('Se borrará el cliente y sus usuarios asociados de la base de datos. Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Eliminar')
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
     * Subnavegación al ver/editar un cliente: Ver, Editar, Puntos de mejora, Empleados.
     *
     * @return array<\Filament\Navigation\NavigationItem>
     */
    public static function getRecordSubNavigation(\Filament\Resources\Pages\Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewClient::class,
            Pages\EditClient::class,
            Pages\PuntosDeMejora::class,
            Pages\Empleados::class,
            Pages\Llamadas::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user?->isSuperAdmin() || $user?->isDistributor() || $user?->isClientOwner() || false;
    }

    /**
     * El ítem "Clientes" no se muestra en el menú para el rol cliente;
     * ellos solo ven "Puntos de mejora" (ítem personalizado en AdminPanelProvider).
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
