<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Database\Eloquent\Model;

class Empleados extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.empleados';

    protected static ?string $title = 'Empleados';

    protected static ?string $navigationLabel = 'Empleados';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        if (! ClientResource::canView($this->getRecord())) {
            abort(403);
        }
    }

    /**
     * SuperAdmin o Distribuidor (solo sus clientes) pueden crear/editar/borrar empleados.
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
            return $client->created_by === $user->id;
        }

        return false;
    }

    public function getEmployees()
    {
        return $this->getRecord()->employees()->orderBy('name')->get();
    }

    public function deleteEmployee(string $id): void
    {
        $employee = Employee::find($id);
        if (! $employee || $employee->client_id !== $this->getRecord()->id) {
            Notification::make()->danger()->title('No autorizado')->send();

            return;
        }
        if (! EmployeeResource::canDelete($employee)) {
            Notification::make()->danger()->title('No puedes eliminar este empleado')->send();

            return;
        }
        $employee->delete();
        Notification::make()->success()->title('Empleado eliminado')->send();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        if ($this->canEditEmpleados()) {
            $client = $this->getRecord();
            $actions[] = Actions\Action::make('create')
                ->label('Añadir empleado')
                ->icon('heroicon-o-plus')
                ->url(EmployeeResource::getUrl('create') . '?client_id=' . $client->id)
                ->color('primary');
        }

        return $actions;
    }

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user && $user->isClientOwner()) {
            return 'Tus empleados';
        }

        return 'Empleados: ' . $this->getRecord()->namecommercial;
    }

    public function getBreadcrumb(): string
    {
        return 'Empleados';
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
