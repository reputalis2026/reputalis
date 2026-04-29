<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Client;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationLabel(): string
    {
        return __('employees.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('employees.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('employees.resource.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $isDistributor = $user?->isDistributor() ?? false;
        $isClientOwner = $user?->isClientOwner() ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make(__('employees.form.section_data'))
                    ->schema([
                        Forms\Components\Hidden::make('client_id')
                            ->default(fn () => request()->query('client_id'))
                            ->required(),
                        Forms\Components\Placeholder::make('client_display')
                            ->label(__('employees.form.client_name'))
                            ->content(fn ($get): string => (string) (\App\Models\Client::find($get('client_id'))?->namecommercial ?? __('common.placeholders.empty')))
                            ->visible(fn ($get): bool => filled($get('client_id'))),
                        Forms\Components\TextInput::make('name')
                            ->label(__('employees.form.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('alias')
                            ->label(__('employees.form.alias'))
                            ->maxLength(100)
                            ->helperText(__('employees.form.alias_help')),
                        Forms\Components\FileUpload::make('photo')
                            ->label(__('employees.form.photo'))
                            ->image()
                            ->disk('public')
                            ->directory('employees')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight(120)
                            ->maxSize(1024),
                        Forms\Components\TextInput::make('position')
                            ->label(__('employees.form.position'))
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('employees.form.active'))
                            ->default(true),
                    ]),
                Forms\Components\Section::make(__('employees.form.section_nfc'))
                    ->icon('heroicon-o-credit-card')
                    ->description(__('employees.form.section_nfc_description'))
                    ->schema([
                        Forms\Components\TextInput::make('nfc_token')
                            ->label(__('employees.form.nfc_token'))
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, ?\App\Models\Employee $record): void {
                                if ($record?->nfcTokens?->token) {
                                    $component->state($record->nfcTokens->token);
                                } else {
                                    $component->state(__('employees.form.nfc_generated_on_save'));
                                }
                            }),
                        Forms\Components\TextInput::make('nfc_survey_url')
                            ->label(__('employees.form.nfc_survey_url'))
                            ->disabled()
                            ->dehydrated(false)
                            // En esta versión de Filament no existe TextInput::copyable().
                            // El copiado se realiza con un botón JS debajo.
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, ?\App\Models\Employee $record): void {
                                if ($record?->nfcTokens?->token) {
                                    $component->state(url('/survey/nfc/'.$record->nfcTokens->token));
                                } else {
                                    $component->state(__('common.placeholders.empty'));
                                }
                            }),
                        Forms\Components\Placeholder::make('copy_nfc_survey_url')
                            ->label('')
                            ->content(function (?\App\Models\Employee $record): ?\Illuminate\Support\HtmlString {
                                $token = $record?->nfcTokens?->token;
                                if (! $token) {
                                    return null;
                                }

                                $url = url('/survey/nfc/'.$token);

                                // Evitamos json_encode() porque mete comillas dobles dentro de un atributo double-quoted.
                                $urlJsLiteral = "'".addslashes($url)."'";
                                $onclick = '(async () => {'.
                                    'try {'.
                                        'await navigator.clipboard.writeText('.$urlJsLiteral.');'.
                                        'window.dispatchEvent(new CustomEvent(\'notificationSent\', { detail: { notification: { title: \''.addslashes(__('employees.actions.link_copied')).'\', status: \'success\' } } }));'.
                                    '} catch (e) {'.
                                        'const ta = document.createElement(\'textarea\');'.
                                        'ta.value = '.$urlJsLiteral.';'.
                                        'ta.setAttribute(\'readonly\', \'\');'.
                                        'ta.style.position = \'fixed\';'.
                                        'ta.style.left = \'-9999px\';'.
                                        'document.body.appendChild(ta);'.
                                        'ta.select();'.
                                        'const ok = document.execCommand(\'copy\');'.
                                        'document.body.removeChild(ta);'.
                                        'if (ok) {'.
                                            'window.dispatchEvent(new CustomEvent(\'notificationSent\', { detail: { notification: { title: \''.addslashes(__('employees.actions.link_copied')).'\', status: \'success\' } } }));'.
                                        '} else {'.
                                            'window.dispatchEvent(new CustomEvent(\'notificationSent\', { detail: { notification: { title: \''.addslashes(__('employees.form.copy_failed')).'\', status: \'danger\' } } }));'.
                                        '}'.
                                    '}'.
                                '})()';

                                return new \Illuminate\Support\HtmlString(
                                    '<x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document" outlined '.
                                    'onclick="'.$onclick.'"'.
                                    '>'.__('common.actions.copy_link').'</x-filament::button>'
                                );
                            }),
                    ])
                    ->columns(1),
            ]);
    }

    protected static function scopeClientQuery(Builder $query): Builder
    {
        $user = auth()->user();
        if ($user?->isSuperAdmin()) {
            return $query->orderBy('namecommercial');
        }
        if ($user?->isDistributor()) {
            return $query->where('created_by', $user->id)->orderBy('namecommercial');
        }
        if ($user?->isClientOwner() && $user->ownedClient) {
            return $query->where('id', $user->ownedClient->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        return $table
            ->emptyStateHeading(__('employees.table.empty_heading'))
            ->emptyStateDescription(__('employees.table.empty_description'))
            ->emptyStateIcon('heroicon-o-user-group')
            ->columns([
                Tables\Columns\TextColumn::make('client.namecommercial')
                    ->label(__('common.fields.client'))
                    ->formatStateUsing(fn ($record) => $record->client ? $record->client->namecommercial.' ('.$record->client->code.')' : __('common.placeholders.empty'))
                    ->searchable(['clients.namecommercial', 'clients.code'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('photo')
                    ->label(__('common.fields.photo'))
                    ->disk('public')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('common.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alias')
                    ->label(__('common.fields.alias'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('common.placeholders.empty')),
                Tables\Columns\TextColumn::make('position')
                    ->label(__('common.fields.position'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('common.placeholders.empty')),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('common.fields.active'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(
                $isSuperAdmin
                    ? [
                        Tables\Filters\SelectFilter::make('client_id')
                            ->label(__('common.fields.client'))
                            ->relationship('client', 'namecommercial')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->namecommercial.' ('.$record->code.')'),
                    ]
                    : []
            )
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('common.actions.view')),
                Tables\Actions\EditAction::make()->label(__('common.actions.edit')),
            ])
            ->bulkActions(
                $isSuperAdmin
                    ? [
                        Tables\Actions\BulkActionGroup::make([
                            Tables\Actions\DeleteBulkAction::make()
                                ->label(__('common.actions.delete_selected'))
                                ->requiresConfirmation()
                                ->modalHeading(__('employees.table.delete_selected_heading'))
                                ->modalDescription(__('employees.table.delete_selected_description'))
                                ->modalSubmitActionLabel(__('common.actions.delete'))
                                ->modalCancelActionLabel(__('common.actions.cancel')),
                        ]),
                    ]
                    : []
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        // Listado global solo superadmin (mantener empleados bajo Cliente para el resto de roles)
        return $user?->isSuperAdmin() ?? false;
    }

    /**
     * Filament (trait CanAuthorizeResourceAccess) ejecuta mountCanAuthorizeResourceAccess → canAccess()
     * antes del mount de CreateRecord. Si esto solo delega en canViewAny(), distribuidores y clientes
     * reciben 403 al abrir /employees/create aunque canCreate() sea true.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return static::canViewAny() || static::canCreate();
    }

    /**
     * Oculto en el menú: la gestión se hace desde Cliente → Empleados (y rol cliente ve "Empleados" en su menú lateral).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDistributor()) {
            return true;
        }
        if ($user->isClientOwner()) {
            // No depender solo de ownedClient (p. ej. relación no hidratada); alinear con ClientResource (owner_id).
            return Client::query()->where('owner_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Cliente contenedor del empleado (incl. solo borrados para comprobar permisos de superadmin).
     */
    protected static function authorizationClient(?Employee $record): ?Client
    {
        if (! $record?->client_id) {
            return null;
        }

        return Client::query()->find($record->client_id)
            ?? Client::onlyTrashed()->find($record->client_id);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (! $user || ! $record instanceof Employee) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        $client = static::authorizationClient($record);
        if (! $client) {
            return false;
        }
        if ($client->trashed() && ! $user->isSuperAdmin()) {
            return false;
        }

        return ClientResource::canEdit($client);
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (! $user || ! $record instanceof Employee) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        $client = static::authorizationClient($record);
        if (! $client) {
            return false;
        }
        if ($client->trashed() && ! $user->isSuperAdmin()) {
            return false;
        }

        return ClientResource::canView($client);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (! $user || ! $record instanceof Employee) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        $client = static::authorizationClient($record);
        if (! $client || $client->trashed()) {
            return false;
        }

        return ClientResource::canEdit($client);
    }

    public static function canDeleteAny(): bool
    {
        return static::canCreate();
    }
}
