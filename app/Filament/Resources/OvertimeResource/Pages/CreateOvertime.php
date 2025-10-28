<?php

namespace App\Filament\Resources\OvertimeResource\Pages;

use App\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Attendance;
use Carbon\Carbon;

class CreateOvertime extends CreateRecord
{
    protected static string $resource = OvertimeResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['attendance_id'])) {
            $attendance = Attendance::where('employee_id', $data['employee_id'])
                ->whereDate('attendance_date', $data['overtime_date'])
                ->first();

            $data['attendance_id'] = $attendance?->id ?? null;
        }
        $data['created_by'] = auth()->user()->email ?? null;
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); 
    }

    protected function afterCreate(): void
    {
        $overtime = $this->record;
        $attendance = Attendance::find($overtime->attendance_id);

        if ($attendance) {
            $attendanceDate = Carbon::parse($attendance->attendance_date)->toDateString();

            $checkOutTime = Carbon::parse($attendanceDate . ' 17:00:00');
            $startOvertime = Carbon::parse($overtime->start_time);

            // Jika lembur mulai di antara jam 17:00-18:00
            if ($startOvertime->between(
                Carbon::parse("17:00:00"),
                Carbon::parse("18:00:00")
            )) {
                // Auto checkout jam 17:00
                $attendance->update([
                    'check_out_time' => $checkOutTime,
                    'updated_by' => 'Auto Checkout',
                ]);

                // Set jam lembur mulai jam 18:00
                // $overtime->update([
                //     'start_time' => Carbon::parse($attendance->attendance_date . ' 18:00:00'),
                // ]);
            }
        }
    }
}
