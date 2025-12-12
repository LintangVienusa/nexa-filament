<?php

namespace App\Filament\Resources\CategoryAssetResource\Pages;

use App\Filament\Resources\CategoryAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryAsset extends EditRecord
{
    protected static string $resource = CategoryAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
