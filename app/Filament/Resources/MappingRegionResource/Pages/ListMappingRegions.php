<?php

namespace App\Filament\Resources\MappingRegionResource\Pages;

use App\Filament\Resources\MappingRegionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMappingRegions extends ListRecords
{
    protected static string $resource = MappingRegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
