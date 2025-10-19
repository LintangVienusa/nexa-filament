<?php

namespace App\Filament\Resources\CategoryAssetResource\Pages;

use App\Filament\Resources\CategoryAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryAssets extends ListRecords
{
    protected static string $resource = CategoryAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
