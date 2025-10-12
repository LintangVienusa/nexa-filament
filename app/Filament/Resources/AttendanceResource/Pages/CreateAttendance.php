<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;
    protected function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'photoTaken' => 'setPhotoEvidence',
        ]);
    }

    public function setPhotoEvidence($path)
    {
        $this->form->fill([
            'check_in_evidence' => $path,
        ]);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
