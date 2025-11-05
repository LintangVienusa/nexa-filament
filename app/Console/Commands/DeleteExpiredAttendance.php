<?php

// namespace App\Console\Commands;

// use Illuminate\Console\Command;
// use App\Models\Attendance;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Carbon\Carbon;

// class DeleteExpiredAttendance extends Command
// {
//     // protected $signature = 'attendance:delete-expired';
//     // protected $description = 'Hapus attendance yang tidak diisi dalam 1 jam';

//     // public function handle()
//     // {
//     //     $oneHourAgo = Carbon::now()->subHour();

//     //    $attendanceIds = DB::connection('mysql_employees')->table('Attendances')
//     //         ->leftJoin('TimeSheets', 'Attendances.id', '=', 'TimeSheets.attendance_id')
//     //         ->whereNull('TimeSheets.id') 
//     //         ->where('Attendances.created_at', '>', Carbon::now()->subHour())
//     //         ->pluck('Attendances.id');

//     //     Attendance::destroy($attendanceIds);

//     //      $message = "Deleted " . count($attendanceIds) . " expired Attendances(s) at " . now();
//     //     // $this->info($message);
//     //     // Log::info($message);
//     // }
// }
 