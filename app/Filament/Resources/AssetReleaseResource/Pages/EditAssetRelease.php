<?php

namespace App\Filament\Resources\AssetReleaseResource\Pages;

use App\Filament\Resources\AssetReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetRelease extends EditRecord
{
    protected static string $resource = AssetReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
