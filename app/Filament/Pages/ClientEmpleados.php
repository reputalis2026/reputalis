<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Employee;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ClientEmpleados extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('client.nav.groups.management');
    }

    protected static string $view = 'filament.pages.client-empleados';

    public static function getNavigationLabel(): string
    {
        return __('employees.navigation_label');
    }

    public function getTitle(): string
    {
        return __('employees.title.own');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public ?Client $client = null;

    public function mount(): void
    {
        $this->client = $this->resolveClient();
        if (! $this->client) {
            abort(404);
        }
    }

    protected function resolveClient(): ?Client
    {
        return \App\Support\ClientPanel::ownedClient();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\ClientPanel::isActive();
    }

    public static function canAccess(): bool
    {
        return \App\Support\ClientPanel::isActive();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        if (! $this->client) {
            return new Collection([]);
        }

        return $this->client->employees()->orderBy('name')->get();
    }
}
