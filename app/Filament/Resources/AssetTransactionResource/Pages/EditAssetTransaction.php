<?php

namespace App\Filament\Resources\AssetTransactionResource\Pages;

use App\Filament\Resources\AssetTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetTransaction extends EditRecord
{
    protected static string $resource = AssetTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
