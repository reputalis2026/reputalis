<?php

namespace App\Support;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\ReputacionExterna;
use App\Models\Client;
use App\Models\Employee;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

class ClientPanel
{
    public const PREVIEW_SESSION_KEY = 'reputalis.client_panel_preview_id';

    public static function isActive(): bool
    {
        if (self::isClientOwnerSession()) {
            return true;
        }

        return self::isPreview();
    }

    public static function isPreview(): bool
    {
        return self::previewClient() !== null;
    }

    public static function isStaffClientCardRoute(): bool
    {
        if (
            self::isEmployeeResourceRoute()
            || self::isClientEmployeesCardRoute()
            || self::isClientFichaRoute()
            || self::isClientEditRoute()
        ) {
            return true;
        }

        $name = (string) request()->route()?->getName();

        foreach ([
            'clients.dashboard',
            'clients.reputacion-externa',
            'clients.puntos-de-mejora',
            'clients.llamadas',
            'pages.client-certificados',
            'pages.client-informes',
            'pages.client-empleados',
        ] as $route) {
            if (str_contains($name, $route)) {
                return true;
            }
        }

        return false;
    }

    public static function showsStaffReturnBar(): bool
    {
        $user = auth()->user();

        if (! $user || $user->isClientOwner()) {
            return false;
        }

        if (! ($user->isSuperAdmin() || $user->isDistributor())) {
            return false;
        }

        if (! self::isStaffClientCardRoute()) {
            return false;
        }

        $client = self::clientFromRoute() ?? self::previewClient();

        return $client !== null && ClientResource::canView($client);
    }

    public static function staffReturnClient(): ?Client
    {
        if (! self::showsStaffReturnBar()) {
            return null;
        }

        $client = self::clientFromRoute() ?? self::previewClient();

        if (! $client || ! ClientResource::canView($client)) {
            return null;
        }

        return $client;
    }

    public static function hidesStaffSidebar(): bool
    {
        if (self::isStaffFullscreenCardScreen()) {
            return true;
        }

        return self::showsStaffReturnBar() && ! self::isClientExperienceRoute();
    }

    public static function isStaffFullscreenCardScreen(): bool
    {
        $user = auth()->user();

        if (! $user || $user->isClientOwner()) {
            return false;
        }

        if (! ($user->isSuperAdmin() || $user->isDistributor())) {
            return false;
        }

        return self::isEmployeeResourceRoute()
            || self::isClientEmployeesCardRoute()
            || self::isClientFichaRoute()
            || self::isClientEditRoute();
    }

    public static function isEmployeeResourceRoute(): bool
    {
        $name = (string) request()->route()?->getName();

        if (str_contains($name, 'resources.employees.')) {
            return true;
        }

        return (bool) preg_match('#(?:^|/)employees(?:/|$)#', request()->path());
    }

    public static function isClientEmployeesCardRoute(): bool
    {
        $name = (string) request()->route()?->getName();

        if (str_contains($name, 'clients.empleados')) {
            return true;
        }

        return (bool) preg_match('#(?:^|/)clients/[^/]+/empleados$#', request()->path());
    }

    public static function isClientFichaRoute(): bool
    {
        $name = (string) request()->route()?->getName();

        if (str_contains($name, 'clients.view')) {
            return true;
        }

        return (bool) preg_match('#(?:^|/)clients/[^/]+/ficha$#', request()->path());
    }

    public static function isClientEditRoute(): bool
    {
        $name = (string) request()->route()?->getName();

        if (str_contains($name, 'clients.edit')) {
            return true;
        }

        return (bool) preg_match('#(?:^|/)clients/[^/]+/edit$#', request()->path());
    }

    public static function allowsAdminNavigation(): bool
    {
        return ! self::isActive();
    }

    public static function ownedClient(): ?Client
    {
        if (self::isClientOwnerSession()) {
            return auth()->user()->ownedClient;
        }

        return self::previewClient();
    }

    public static function enterPreview(Client $client): void
    {
        $user = auth()->user();

        if (! $user || $user->isClientOwner()) {
            return;
        }

        if (! ($user->isSuperAdmin() || $user->isDistributor())) {
            return;
        }

        if (! ClientResource::canView($client)) {
            return;
        }

        session([self::PREVIEW_SESSION_KEY => $client->getKey()]);
    }

    public static function exitPreview(): void
    {
        session()->forget(self::PREVIEW_SESSION_KEY);
    }

    public static function previewClient(): ?Client
    {
        $user = auth()->user();

        if (! $user || $user->isClientOwner()) {
            return null;
        }

        if (! ($user->isSuperAdmin() || $user->isDistributor())) {
            return null;
        }

        $id = session(self::PREVIEW_SESSION_KEY);

        if (! filled($id)) {
            return null;
        }

        $client = Client::query()->find($id);

        if (! $client || ! ClientResource::canView($client)) {
            self::exitPreview();

            return null;
        }

        return $client;
    }

    public static function previewExitUrl(?Client $client = null): ?string
    {
        $client ??= self::staffReturnClient() ?? self::previewClient();

        if (! $client) {
            return ClientResource::getUrl('index');
        }

        return ClientResource::getUrl('hub', ['record' => $client]);
    }

    /**
     * @return array<int, NavigationGroup>
     */
    public static function navigationGroups(): array
    {
        return [
            NavigationGroup::make(fn (): string => __('client.nav.groups.main'))
                ->collapsible(false),
            NavigationGroup::make(fn (): string => __('client.nav.groups.management'))
                ->collapsible(false),
            NavigationGroup::make(fn (): string => __('client.nav.groups.documents'))
                ->collapsible(false),
        ];
    }

    /**
     * @return array<int, NavigationItem>
     */
    public static function navigationItems(): array
    {
        return [
            NavigationItem::make(fn (): string => __('client.dashboard.tabs.internal'))
                ->icon('heroicon-o-check-circle')
                ->group(fn (): string => __('client.nav.groups.main'))
                ->sort(1)
                ->visible(fn (): bool => self::isActive())
                ->url(fn (): string => self::internalUrl() ?? '#')
                ->isActiveWhen(fn (): bool => self::isInternalActive()),
            NavigationItem::make(fn (): string => __('client.dashboard.tabs.external'))
                ->icon('heroicon-o-arrow-trending-up')
                ->group(fn (): string => __('client.nav.groups.main'))
                ->sort(2)
                ->visible(fn (): bool => self::isActive())
                ->url(fn (): string => self::externalUrl() ?? '#')
                ->isActiveWhen(fn (): bool => self::isExternalActive()),
            NavigationItem::make(fn (): string => __('client.dashboard.tabs.sector'))
                ->icon('heroicon-o-chart-bar')
                ->group(fn (): string => __('client.nav.groups.main'))
                ->sort(3)
                ->visible(fn (): bool => self::isActive())
                ->url(fn (): string => self::sectorUrl() ?? '#')
                ->isActiveWhen(fn (): bool => self::isSectorActive()),
        ];
    }

    public static function internalUrl(): ?string
    {
        $client = self::ownedClient();

        return $client ? ClientResource::getUrl('dashboard', ['record' => $client]) : null;
    }

    public static function externalUrl(): ?string
    {
        $client = self::ownedClient();

        return $client ? ReputacionExterna::getUrl(['record' => $client]) : null;
    }

    public static function sectorUrl(): ?string
    {
        $internal = self::internalUrl();

        return $internal ? $internal.'?reputationTab=sector' : null;
    }

    public static function isInternalActive(): bool
    {
        return self::isDashboardRoute() && request()->query('reputationTab') !== 'sector';
    }

    public static function isSectorActive(): bool
    {
        return self::isDashboardRoute() && request()->query('reputationTab') === 'sector';
    }

    public static function isExternalActive(): bool
    {
        $url = self::externalUrl();

        return filled($url) && url()->current() === rtrim($url, '/');
    }

    public static function isClientDashboardRoute(): bool
    {
        $name = (string) request()->route()?->getName();

        return str_contains($name, 'clients.dashboard')
            || str_contains($name, 'clients.reputacion-externa');
    }

    public static function isClientExperienceRoute(): bool
    {
        if (self::isClientDashboardRoute()) {
            return true;
        }

        $name = (string) request()->route()?->getName();

        foreach ([
            'pages.client-certificados',
            'pages.client-informes',
            'pages.client-empleados',
        ] as $route) {
            if (str_contains($name, $route)) {
                return true;
            }
        }

        return false;
    }

    public static function clientFromRoute(): ?Client
    {
        $clientId = request()->query('client_id');
        if (filled($clientId)) {
            $client = Client::query()->find($clientId);
            if ($client) {
                return $client;
            }
        }

        $livewireClient = self::clientFromLivewire();
        if ($livewireClient) {
            return $livewireClient;
        }

        $record = request()->route('record');

        if ($record instanceof Client) {
            return $record;
        }

        if ($record instanceof Employee) {
            return self::clientFromEmployee($record);
        }

        if (self::isEmployeeResourceRoute() && filled($record) && ! $record instanceof Model) {
            return self::clientFromEmployee(Employee::query()->find($record));
        }

        if ($record instanceof Model) {
            return Client::query()->find($record->getKey());
        }

        if (filled($record)) {
            return Client::query()->find($record);
        }

        return null;
    }

    private static function clientFromLivewire(): ?Client
    {
        try {
            $component = Livewire::current();
        } catch (\Throwable) {
            return null;
        }

        if (! $component) {
            return null;
        }

        $record = $component->record ?? null;

        if ($record instanceof Client) {
            return $record;
        }

        if ($record instanceof Employee) {
            return self::clientFromEmployee($record);
        }

        return null;
    }

    private static function clientFromEmployee(?Employee $employee): ?Client
    {
        if (! $employee) {
            return null;
        }

        return $employee->client ?? Client::query()->find($employee->client_id);
    }

    private static function isClientOwnerSession(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    private static function isDashboardRoute(): bool
    {
        $url = self::internalUrl();

        return filled($url) && url()->current() === rtrim($url, '/');
    }
}
