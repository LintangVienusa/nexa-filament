<?php

namespace App\Filament\Resources\AssetMovementResource\Pages;

use App\Filament\Resources\AssetMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetMovement extends CreateRecord
{
    protected static string $resource = AssetMovementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
