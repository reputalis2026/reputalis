<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\Concerns\HasClientPageTitle;
use App\Models\Client;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class ClientHub extends Page
{
    use HasClientPageTitle;
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.client-hub';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        \App\Support\ClientPanel::exitPreview();

        if (auth()->user()?->isClientOwner()) {
            $this->redirect(ClientResource::getUrl('dashboard', ['record' => $this->record]));
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('client.pages.hub_title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubNavigationParameters(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubNavigation(): array
    {
        return [];
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function canSeeCalls(): bool
    {
        $user = auth()->user();
        $client = $this->getClientRecord();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isDistributor()) {
            return $client->created_by === $user->id;
        }

        return false;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(ClientResource::canView($this->getClientRecord()), 403);
    }

    protected function getClientRecord(): Client
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return $client;
    }
}
