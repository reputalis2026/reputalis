<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Employee;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ClientEmpleados extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.client-empleados';

    protected static ?string $title = 'Tus empleados';

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
        $user = auth()->user();
        if (! $user || ! $user->isClientOwner()) {
            return null;
        }

        return $user->ownedClient;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
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
