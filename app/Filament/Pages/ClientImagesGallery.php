<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Employee;
use App\Support\ClientImagePaths;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClientImagesGallery extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static string $view = 'filament.pages.client-images-gallery';

    public ?string $clientId = null;

    public ?string $employeeId = null;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('panel.client_images.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel.client_images.title');
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
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() === true || $user?->isDistributor() === true;
    }

    public function mount(): void
    {
        $clientId = request()->query('client');
        if (filled($clientId) && ! $this->scopedClientsQuery()->whereKey($clientId)->exists()) {
            $clientId = null;
        }

        $this->clientId = $clientId;
        $this->employeeId = null;
        $this->form->fill([
            'clientId' => $this->clientId,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('clientId')
                    ->label(__('panel.client_images.select_client'))
                    ->placeholder(__('panel.client_images.filter_placeholder'))
                    ->options(fn (): array => $this->clientOptions())
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function (?string $state): void {
                        if (! filled($state) || ! $this->scopedClientsQuery()->whereKey($state)->exists()) {
                            $this->clientId = null;
                            $this->employeeId = null;

                            return;
                        }

                        $this->clientId = $state;
                        $this->employeeId = null;
                    }),
            ])
            ->statePath('data');
    }

    /**
     * @return array<string, string>
     */
    protected function clientOptions(): array
    {
        return $this->scopedClientsQuery()
            ->orderBy('namecommercial')
            ->get(['id', 'namecommercial', 'code'])
            ->mapWithKeys(fn (Client $client) => [
                $client->id => $client->namecommercial,
            ])
            ->all();
    }

    protected function scopedClientsQuery(): Builder
    {
        $query = Client::query();
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query;
        }

        if ($user?->isDistributor()) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function getSelectedClient(): ?Client
    {
        if (! filled($this->clientId)) {
            return null;
        }

        return $this->scopedClientsQuery()
            ->with(['employees' => fn ($q) => $q->orderBy('name')])
            ->find($this->clientId);
    }

    /**
     * @return Collection<int, array{path: string, url: string, label: string, is_current: bool}>
     */
    public function getBusinessImagesProperty(): Collection
    {
        $client = $this->getSelectedClient();

        return $client
            ? collect(ClientImagePaths::listBusinessImages($client))
            : collect();
    }

    /**
     * @return Collection<int, array{employee: Employee, current_url: ?string, count: int, images: array}>
     */
    public function getEmployeeFoldersProperty(): Collection
    {
        $client = $this->getSelectedClient();

        return $client
            ? collect(ClientImagePaths::listEmployeeFolders($client))
            : collect();
    }

    public function getSelectedEmployee(): ?Employee
    {
        $client = $this->getSelectedClient();
        if (! $client || ! filled($this->employeeId)) {
            return null;
        }

        return $client->employees->firstWhere('id', $this->employeeId);
    }

    /**
     * @return Collection<int, array{path: string, url: string, label: string, is_current: bool}>
     */
    public function getEmployeeImagesProperty(): Collection
    {
        $client = $this->getSelectedClient();
        $employee = $this->getSelectedEmployee();

        if (! $client || ! $employee) {
            return collect();
        }

        return collect(ClientImagePaths::listImagesForEmployee($client, $employee));
    }

    /**
     * @return Collection<int, array{client: Client, logo_url: ?string, initials: string}>
     */
    public function getClientRowsProperty(): Collection
    {
        return $this->scopedClientsQuery()
            ->orderBy('namecommercial')
            ->get(['id', 'namecommercial', 'code', 'logo'])
            ->map(function (Client $client) {
                $logoUrl = null;
                if (filled($client->logo)) {
                    $logoUrl = ClientImagePaths::publicUrl($client->logo);
                }

                return [
                    'client' => $client,
                    'logo_url' => $logoUrl,
                    'initials' => mb_strtoupper(mb_substr((string) $client->namecommercial, 0, 1)),
                ];
            })
            ->values();
    }

    public function toggleClient(string $clientId): void
    {
        if (! $this->scopedClientsQuery()->whereKey($clientId)->exists()) {
            return;
        }

        if ($this->clientId === $clientId) {
            $this->clientId = null;
            $this->employeeId = null;
            $this->form->fill(['clientId' => null]);

            return;
        }

        $this->clientId = $clientId;
        $this->employeeId = null;
        $this->form->fill(['clientId' => $clientId]);
    }

    public function openEmployee(string $employeeId): void
    {
        $client = $this->getSelectedClient();
        if (! $client || ! $client->employees->contains('id', $employeeId)) {
            return;
        }

        $this->employeeId = $employeeId;
    }

    public function clearEmployee(): void
    {
        $this->employeeId = null;
    }

    public function clearFilter(): void
    {
        $this->clientId = null;
        $this->employeeId = null;
        $this->form->fill(['clientId' => null]);
    }
}
