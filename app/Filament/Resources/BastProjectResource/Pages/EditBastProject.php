<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBastProject extends EditRecord
{
    protected static string $resource = BastProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
