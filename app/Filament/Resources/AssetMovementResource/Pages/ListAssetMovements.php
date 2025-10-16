<?php

namespace App\Filament\Resources\AssetMovementResource\Pages;

use App\Filament\Resources\AssetMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetMovements extends ListRecords
{
    protected static string $resource = AssetMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
