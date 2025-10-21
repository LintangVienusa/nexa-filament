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
            $this->form->fill([
                'employee_id' => $this->record->employee_id,
                'timesheet_date' => $this->record->timesheet_date ?? now(),
                'attendance_id' => $this->record->attendance_id,
                'job_description' => $this->record->job_description,
                'job_duration' => $this->record->job_duration,
                'status' => $this->record->status,
                'attendance_info' => $this->record->attendance_id
                    ? '✅ Attendance tersedia' 
                    : '❌ Tidak ada attendance',
            ]);
        } else {
            
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
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record) {
            $createdAt = $this->record->created_at instanceof \Carbon\Carbon
                ? $this->record->created_at
                : \Carbon\Carbon::parse($this->record->created_at);

            $data['job_duration'] = $createdAt->diffInMinutes(now());
        } else {
            $data['job_duration'] = 0;
        }

        return $data;
    }
}
