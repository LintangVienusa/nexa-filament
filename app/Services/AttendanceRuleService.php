<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Timesheet;
use Filament\Notifications\Notification;

class AttendanceRuleService
{
    public static function applyRules(Attendance $attendance): void
    {
        $checkinTime = $attendance->check_in_time
            ? Carbon::parse($attendance->check_in_time)
            : null;

        $now = now();
        $date = $attendance->attendance_date instanceof Carbon
            ? $attendance->attendance_date
            : Carbon::parse($attendance->attendance_date);

        $status = '3';
        $info = 'Belum check-in atau di luar jam kerja';

        $limitTask = $date->copy()->setTime(9, 15);
        $hasTimesheet = Timesheet::where('attendance_id', $attendance->id)->exists();

        if ($now->gt($limitTask) && ! $hasTimesheet) {
            $status = '3'; // 2 = Alpha
            $info = 'Tidak input task sebelum 09:15 WIB';
        }

        if ($checkinTime) {
            $startin =$date->copy()->setTime(7, 0);
            if ($checkinTime->between($date->copy()->setTime(7, 0), $date->copy()->setTime(8, 15))) {
                $status = '0';
                $info = 'Check-in dalam waktu normal (07:00 - 08:15)';
            } elseif ($checkinTime->gt($date->copy()->setTime(8, 15))) {
                $status = '2';
                $info = 'Terlambat check-in setelah 08:15 WIB';
            }
        }

        

        $attendance->update([
            'status' => $status,
            'notes' => $info,
        ]);

         Notification::make()
            ->title('Status Absensi Diperbarui')
            ->body($info)
            ->success()
            ->send();
    }
}