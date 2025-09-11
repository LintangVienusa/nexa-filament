<?php

namespace App\Filament\Resources\SalaryComponentResource\Pages;

use App\Filament\Resources\SalaryComponentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalaryComponent extends EditRecord
{
    protected static string $resource = SalaryComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
