<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Attendance;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['attendance_id'])) {
            $attendance = Attendance::where('employee_id', $data['employee_id'])
                ->whereDate('attendance_date', $data['timesheet_date'])
                ->first();

            $data['attendance_id'] = $attendance?->id ?? null;
        }
        $data['created_by'] = auth()->user()->email ?? null;
        return $data;
    }

    protected function getFormModel(): string
    {
        return \App\Models\Timesheet::class;
    }

    protected function getFormSchema(): array
    {
        return TimesheetResource::form($this)->getSchema();
    }

    public function mount(): void
    {
        parent::mount();

        $employeeId = auth()->user()->employee?->employee_id;

        $attendance = \App\Models\Attendance::where('employee_id', $employeeId)
            ->latest('attendance_date')
            ->first();

        $this->form->fill([
            'employee_id' => $employeeId,
            'timesheet_date' => $attendance?->attendance_date ?? now(),
            'attendance_id' => $attendance?->id,
            'attendance_info' => $attendance
                ? \App\Filament\Resources\TimesheetResource::formatAttendanceInfo($attendance)
                : '❌ Tidak ditemukan attendance hari ini.',
        ]);

        
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
