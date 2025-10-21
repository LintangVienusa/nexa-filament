<?php

namespace App\Filament\Resources\AssetTransactionResource\Pages;

use App\Filament\Resources\AssetTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetTransactions extends ListRecords
{
    protected static string $resource = AssetTransactionResource::class;
    
    protected static ?string $title = 'Transaksi Asset';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
