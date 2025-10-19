<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     if (!empty($data['check_out_evidence'])) {
    //     // Ambil record yang sedang di-edit
    //     $this->record->update([
    //         'check_out_evidence' => $data['check_out_evidence'],
    //     ]);

    //     // Hapus dari $data agar Filament tidak mencoba update lagi
    //     unset($data['check_out_evidence']);
    //      }

    //     return $data;
    // }

    protected function afterSave(): void
    {
        if ($this->data['check_out_evidence'] ?? false) {
            $this->record->update([
                'check_out_evidence' => $this->data['check_out_evidence'],
            ]);
        }
    }



    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
