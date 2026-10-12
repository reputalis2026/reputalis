<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DistributorSupport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.distributor-support';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('panel.distributors.support_nav');
    }

    public function getTitle(): string
    {
        return __('panel.distributors.support_nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.navigation_groups.configuration');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\ClientPanel::allowsAdminNavigation() && self::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isDistributor() === true && $user->ownedClient !== null;
    }

    public function mount(): void
    {
        $client = $this->distributor();
        $this->form->fill([
            'support_phone' => $client->support_phone,
            'support_email' => $client->support_email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('panel.distributors.support_section'))
                    ->description(__('panel.distributors.support_help'))
                    ->schema([
                        TextInput::make('support_phone')
                            ->label(__('panel.distributors.support_phone'))
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        TextInput::make('support_email')
                            ->label(__('panel.distributors.support_email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $client = $this->distributor();
        $client->support_phone = $data['support_phone'];
        $client->support_email = $data['support_email'];
        $client->save();

        Notification::make()
            ->success()
            ->title(__('panel.distributors.support_saved'))
            ->send();
    }

    private function distributor(): Client
    {
        $client = auth()->user()?->ownedClient;
        abort_unless($client, 403);

        return $client;
    }
}
