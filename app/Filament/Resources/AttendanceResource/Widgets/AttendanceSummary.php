<?php

namespace App\Filament\Resources\AttendanceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\HariKerjaService;

class AttendanceSummary extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;
    


    protected function getStats(): array
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $employee = Auth::user()->employee;
        $employeeId = $employee?->employee_id;

        // $total = Attendance::where('employee_id', $employeeId)->count();
        $startPeriod = Carbon::create(2025, 9, 28)->startOfDay();
        $endPeriod = Carbon::create(2025, 10, 27)->endOfDay();
        $periodName = $startPeriod->format('d M') . ' - ' . $endPeriod->format('d M');

       $attendanceDates = Attendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startPeriod, $endPeriod])
            ->pluck('attendance_date') 
            ->map(fn($d) => Carbon::parse($d)->toDateString()) 
            ->toArray(); 

        $monthName = today()->format('F Y');

        $todayHours = Attendance::where('employee_id', $employeeId)
            ->whereDate('attendance_date', today())
            ->get()
            ->sum(function ($attendance) {
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : now();
                return $checkIn->floatDiffInHours($checkOut);
            });
        $hoursToday = floor($todayHours);
        $minutesToday = round(($todayHours - $hoursToday) * 60);
        $formattedToday = "{$hoursToday} jam {$minutesToday} menit";

        $allDates = [];
        $current = $startPeriod->copy();
        while ($current <= $endPeriod) {
            if (!$current->isWeekend()) {
                $allDates[] = $current->toDateString();
            }
            $current->addDay();
        }

        $absentDays = array_diff($allDates, $attendanceDates);
        $totalAbsent = count($absentDays);
        $totalAttendance = count($attendanceDates);
        $hariKerjaService = new HariKerjaService();
        $data = $hariKerjaService->hitungHariKerja($employeeId, $startPeriod, $endPeriod);

        return [
            
            Stat::make('Total Kehadiran '. $periodName, $data['jml_absensi'])
                ->description('Semua data absensi')
                ->color('success'),

            Stat::make('Hari Tidak Hadir '. $periodName, $data['jml_alpha'])
                ->description('Jumlah hari kerja tanpa absensi')
                ->color('danger'),

            Stat::make('Hari Ini', $formattedToday)
                ->description('Total jam kerja hari ini')
                ->color('info'),
        ];
    }
}
