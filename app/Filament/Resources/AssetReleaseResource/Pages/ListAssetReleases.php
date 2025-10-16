<?php

namespace App\Filament\Resources\AssetReleaseResource\Pages;

use App\Filament\Resources\AssetReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetReleases extends ListRecords
{
    protected static string $resource = AssetReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
