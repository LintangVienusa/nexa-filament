<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\Action;
use Filament\Notifications\Notification;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->visible(fn ($record) => (int)$record->status === 0)
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 1]);
                    Notification::make()->title('Payroll Approved')->success()->send();
                }),

            Action::make('paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-currency-dollar')
                ->visible(fn ($record) => (int)$record->status === 1)
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update([
                        'status' => 2,
                    ]);
                    Notification::make()->title('Payroll marked as Paid')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    
}
