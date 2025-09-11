<?php

namespace App\Filament\Resources\SalaryComponentResource\Pages;

use App\Filament\Resources\SalaryComponentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryComponents extends ListRecords
{
    protected static string $resource = SalaryComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
