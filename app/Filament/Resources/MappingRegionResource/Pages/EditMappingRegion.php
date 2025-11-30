<?php

namespace App\Filament\Resources\MappingRegionResource\Pages;

use App\Filament\Resources\MappingRegionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMappingRegion extends EditRecord
{
    protected static string $resource = MappingRegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
