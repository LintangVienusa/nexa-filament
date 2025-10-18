<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;
    // protected $listeners = ['photoTaken'];

    // public function mount(): void
    // {
    //     parent::mount();

    //     $this->form->fill([
            
    //     'attendance_date' => now('Asia/Jakarta')->format('Y-m-d'),
    //         'employee_id' => auth()->user()->employee?->employee_id,
    //         'employee_nik' => auth()->user()->employee?->employee_id,
    //         'check_in_evidence' => null,
    //     ]);
    // }

    // public function photoTaken($photoBase64)
    // {
    //     $this->form->fill([
    //     'check_in_evidence' => $photoBase64,
    //     'check_in_evidence_display' => $photoBase64,
    //     ]);
    // }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
