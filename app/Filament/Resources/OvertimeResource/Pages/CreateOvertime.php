<?php

namespace App\Filament\Resources\OvertimeResource\Pages;

use App\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Attendance;
use App\Models\Overtime;
use Carbon\Carbon;
use Filament\Notifications\Notification;

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

        $exists = Overtime::where('employee_id', $data['employee_id'])
                ->where('attendance_id', $data['attendance_id'])
                ->exists();

            if ($exists) {
                
                 Notification::make()
                        ->title('Overtime di tgl '.$data['overtime_date']. ' sudah ada!')
                        ->body('Overtime di tgl '.$data['overtime_date']. ' sudah ada!')
                        ->danger()
                        ->send();

                    $this->halt(); 
            }


        $todayf = Carbon::parse($data['overtime_date'], 'Asia/Jakarta');

        
        
        if ($todayf->day >= 28) {
            $startPeriod = $todayf->copy()->day(28)->startOfDay();
            $endPeriod = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
        } else {
            $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
            $endPeriod = $todayf->copy()->day(27)->endOfDay();
        }
        
        

         $totalOvertime = Overtime::query()
            ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
            ->where('Overtimes.employee_id', $data['employee_id'])
            ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
            ->sum('Overtimes.working_hours');

        $startTime = Carbon::parse($data['start_time']);
        $endTime   = Carbon::parse($data['end_time']);
        $newHours  = $startTime->floatDiffInHours($endTime);

        

        if (($totalOvertime + $newHours) > 60) {
            Notification::make()
                        ->title('Overtimes Lebih dari 60 jam')
                        ->body('Overtimes Lebih dari 60 jam.')
                        ->danger()
                        ->send();

                    $this->halt(); 
        }


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
