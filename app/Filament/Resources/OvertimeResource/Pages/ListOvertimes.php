<?php

namespace App\Filament\Resources\OvertimeResource\Pages;

use App\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\OvertimeResource\Widgets\OvertimesWidget;

class ListOvertimes extends ListRecords
{
    protected static string $resource = OvertimeResource::class;

     protected function getHeaderWidgets(): array
    {
        return [
            OvertimesWidget::class,
        ];
    }
    

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
