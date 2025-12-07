<?php

namespace App\Filament\Resources\InventoryAssetResource\Pages;

use App\Filament\Resources\InventoryAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryAsset extends EditRecord
{
    protected static string $resource = InventoryAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
