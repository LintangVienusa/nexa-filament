<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimesheet extends EditRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(string|int $record): void
    {
        parent::mount($record);

    $this->record = $this->getRecord();

    $employeeId = auth()->user()->employee?->employee_id;

    if ($this->record) {
        // Edit → ambil dari record
        $this->form->fill([
            'employee_id' => $this->record->employee_id,
            'timesheet_date' => $this->record->timesheet_date ?? now(),
            'attendance_id' => $this->record->attendance_id,
            'job_description' => $this->record->job_description,
            'status' => $this->record->status,
            'attendance_info' => $this->record->attendance_id
                ? '✅ Attendance tersedia' // atau bisa tampilkan info sesuai kebutuhan
                : '❌ Tidak ada attendance',
        ]);
    } else {
        // Create → ambil attendance terbaru
        $attendance = \App\Models\Attendance::where('employee_id', $employeeId)
            ->latest('attendance_date')
            ->first();

        $this->form->fill([
            'employee_id' => $employeeId,
            'timesheet_date' => $attendance?->attendance_date ?? now(),
            'attendance_id' => $attendance?->id,
            
            'attendance_info' => $attendance
                ? '✅ Attendance tersedia'
                : '❌ Tidak ditemukan attendance hari ini.',
        ]);
    }
    }
}
