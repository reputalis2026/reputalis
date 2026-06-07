<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\Concerns\HasClientPageTitle;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class Empleados extends Page
{
    use HasClientPageTitle;
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.empleados';

    public string $employeeStatusTab = 'active';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.employees');
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        $this->employeeStatusTab = request()->query('employee_status') === 'inactive' ? 'inactive' : 'active';
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        if ($user?->isClientOwner()) {
            abort(403);
        }

        if (! ClientResource::canView($this->getRecord())) {
            abort(403);
        }
    }

    /**
     * Quien puede editar el cliente también puede gestionar sus empleados (dueño, distribuidor, superadmin).
     */
    public function canEditEmpleados(): bool
    {
        $user = auth()->user();
        $client = $this->getRecord();

        if (! $user || ! $client) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isDistributor()) {
            return (string) $client->created_by === (string) $user->id;
        }

        return false;
    }

    public function getEmployees()
    {
        return $this->getRecord()->employees()
            ->where('is_active', $this->employeeStatusTab === 'active')
            ->orderBy('name')
            ->get();
    }

    public function getEmployeesCount(): int
    {
        return $this->getRecord()->employees()->count();
    }

    public function switchEmployeeStatusTab(string $tab): void
    {
        $this->employeeStatusTab = $tab === 'inactive' ? 'inactive' : 'active';
    }

    public function deleteEmployee(string $id): void
    {
        $employee = Employee::find($id);
        if (! $employee || $employee->client_id !== $this->getRecord()->id) {
            Notification::make()->danger()->title(__('common.messages.not_authorized'))->send();

            return;
        }
        if ($employee->is_active) {
            Notification::make()->danger()->title(__('employees.actions.delete_active_forbidden'))->send();

            return;
        }
        if (! EmployeeResource::canDelete($employee)) {
            Notification::make()->danger()->title(__('employees.actions.delete_forbidden'))->send();

            return;
        }
        $employee->delete();
        Notification::make()->success()->title(__('employees.actions.deleted'))->send();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        if ($this->canEditEmpleados()) {
            $client = $this->getRecord();
            $actions[] = Actions\Action::make('create')
                ->label(__('employees.actions.add'))
                ->icon('heroicon-o-plus')
                ->url(EmployeeResource::getUrl('create').'?client_id='.$client->id)
                ->color('primary');
        }

        return $actions;
    }

    public function getBreadcrumb(): string
    {
        return __('client.menu.employees');
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

    public static function getNavigationUrl(array $parameters = []): string
    {
        $record = $parameters['record'] ?? null;

        return $record
            ? ClientResource::getUrl('empleados', ['record' => $record])
            : ClientResource::getUrl('index');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        if (! isset($parameters['record'])) {
            return false;
        }

        return ClientResource::canView($parameters['record']);
    }
}
