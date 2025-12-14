<?php

namespace App\Filament\Resources\HomeConnectReportResource\Pages;
use App\Filament\Resources\HomeConnectResource\Widgets\HomeConnectStats;
use App\Filament\Resources\HomeConnectReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeConnectReports extends ListRecords
{
    protected static string $resource = HomeConnectReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HomeConnectStats::class,
        ];
    }
}
