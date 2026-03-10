<?php

namespace App\Filament\Resources\DistributorResource\Pages;

use App\Filament\Resources\DistributorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistributors extends ListRecords
{
    protected static string $resource = DistributorResource::class;

    public function getTitle(): string
    {
        return 'Distribuidores';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear distribuidor')
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->orderBy('is_active', 'desc')->orderBy('namecommercial', 'asc');
    }

    public function getTableEmptyStateHeading(): ?string
    {
        return 'No hay distribuidores';
    }

    public function getTableEmptyStateDescription(): ?string
    {
        return 'Crea tu primer distribuidor para comenzar.';
    }
}
