<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\Action;
use Filament\Notifications\Notification;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
            Actions\DeleteAction::make(),
        ];
    }

    public static function edit(Leave $record): static
    {
        return parent::edit($record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
