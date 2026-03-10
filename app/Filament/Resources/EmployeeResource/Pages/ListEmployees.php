<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return 'Empleados';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Empleado'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query;
        }
        if ($user?->isDistributor()) {
            return $query->whereHas('client', fn (Builder $q) => $q->where('created_by', $user->id));
        }
        if ($user?->isClientOwner() && $user->ownedClient) {
            return $query->where('client_id', $user->ownedClient->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
