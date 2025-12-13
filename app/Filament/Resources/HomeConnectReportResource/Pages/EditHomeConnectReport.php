<?php

namespace App\Filament\Resources\HomeConnectReportResource\Pages;

use App\Filament\Resources\HomeConnectReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeConnectReport extends EditRecord
{
    protected static string $resource = HomeConnectReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
