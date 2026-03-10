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

    protected static ?string $navigationLabel = 'Distribuidores';

    protected static ?string $modelLabel = 'Distribuidor';

    protected static ?string $pluralModelLabel = 'Distribuidores';

    protected static ?string $slug = 'distribuidores';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('owner', fn (Builder $q) => $q->where('role', User::ROLE_DISTRIBUIDOR));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Logo')
                    ->icon('heroicon-o-photo')
                    ->description('Se mostrará en la esquina superior izquierda del panel cuando el usuario del distribuidor inicie sesión.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo del distribuidor')
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
                                $livewire instanceof Pages\CreateDistributor ||
                                $livewire instanceof Pages\EditDistributor
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
                        $livewire instanceof Pages\CreateDistributor ||
                        $livewire instanceof Pages\EditDistributor
                    ),

                Forms\Components\Section::make('Acceso a Plataforma')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\TextInput::make('access_email')
                            ->label('Usuario')
                            ->required()
                            ->unique('users', 'email')
                            ->maxLength(255)
                            ->helperText('Usuario o correo para acceso a la plataforma.')
                            ->placeholder('Usuario o email')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\CreateDistributor),
                        Forms\Components\TextInput::make('access_password')
                            ->label('Contraseña')
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
                            ->helperText('Mínimo 8 caracteres'),
                        Forms\Components\TextInput::make('access_password_confirmation')
                            ->label('Confirmar')
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
                            ->label('Cambiar contraseña')
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
                    ->label('Cliente activo')
                    ->default(false)
                    ->live()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditDistributor)
                    ->disabled(fn () => ! auth()->user()?->isSuperAdmin())
                    ->helperText(fn () => auth()->user()?->isSuperAdmin() ? 'Solo superadmin puede activar/desactivar.' : null),
                Forms\Components\DatePicker::make('fecha_fin')
                    ->label('Fecha de fin (expiración)')
                    ->displayFormat('d/m/Y')
                    ->helperText('Obligatoria al activar el cliente: fecha hasta la que estará activo.')
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
                InfolistSection::make('Datos de Facturación')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('code')->label('Código'),
                        TextEntry::make('fecha_inicio_alta')->label('Fecha de inicio de alta')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('fecha_fin')->label('Fecha de fin (expiración)')->date('d/m/Y')->placeholder('—'),
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
                        TextEntry::make('owner.id')->label('Contraseña')->formatStateUsing(fn () => '••••••')->placeholder('—'),
                    ])
                    ->columns(2),
                InfolistSection::make('Estado')
                    ->schema([
                        TextEntry::make('is_active')->label('Activo')->badge()->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')->color(fn ($state) => $state ? 'success' : 'gray'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No hay distribuidores')
            ->emptyStateDescription('Crea tu primer distribuidor para comenzar.')
            ->emptyStateIcon('heroicon-o-truck')
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
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\EditAction::make()->label('Editar'),
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
