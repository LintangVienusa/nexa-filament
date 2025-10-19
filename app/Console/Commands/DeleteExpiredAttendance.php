<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeleteExpiredAttendance extends Command
{
    protected $signature = 'attendance:delete-expired';
    protected $description = 'Hapus attendance yang tidak diisi dalam 1 jam';

    public function handle()
    {
        $oneHourAgo = Carbon::now()->subHour();

       $attendanceIds = DB::table('Attendance')
            ->leftJoin('TimeSheets', 'Attendance.id', '=', 'TimeSheets.attendance_id')
            ->whereNull('TimeSheets.id') // belum ada timesheet
            ->where('Attendance.created_at', '<', Carbon::now()->subHour())
            ->pluck('Attendance.id');

        // Hapus attendance
        Attendance::destroy($attendanceIds);
    }
}
