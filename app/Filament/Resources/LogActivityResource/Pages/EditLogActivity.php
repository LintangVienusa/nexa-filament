<?php

namespace App\Filament\Resources\LogActivityResource\Pages;

use App\Filament\Resources\LogActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLogActivity extends EditRecord
{
    protected static string $resource = LogActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
