<?php

namespace App\Filament\Resources\AttendanceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\HariKerjaService;

class AttendanceStatusWidget extends BaseWidget
{
     protected function getStats(): array
    {
        $today = Carbon::now('Asia/Jakarta');
        $employee = Auth::user()->employee;
        $employeeId = $employee?->employee_id;

        // Tentukan periode 28 - 27
        if ($today->day >= 28) {
            $startPeriod = $today->copy()->day(28)->startOfDay();
            $endPeriod = $today->copy()->addMonthNoOverflow()->day(27)->endOfDay();
        } else {
            $startPeriod = $today->copy()->subMonthNoOverflow()->day(28)->startOfDay();
            $endPeriod = $today->copy()->day(27)->endOfDay();
        }

        $periodName = $startPeriod->format('d M') . ' - ' . $endPeriod->format('d M');

        // Ambil data absensi periode ini
        $attendances = Attendance::where('employee_id', $employeeId)
            ->where('status', 0)
            ->whereBetween('attendance_date', [$startPeriod, $endPeriod])
            ->get();

        // Ambil total hari kerja dari service
        $hariKerjaService = new HariKerjaService();
        $data = $hariKerjaService->hitungHariKerja($employeeId, $startPeriod, $endPeriod);
        $totalHariKerja = $data['jumlah_hari_kerja'] ?? 0;

        // Hitung jumlah hadir per status
        $onTime = $attendances->where('status', 0)->count();
        $late   = $attendances->where('status', 2)->count();

        // Alpha dihitung dari selisih total hari kerja dan total hadir
        $alpha  = max($totalHariKerja - ($onTime + $late), 0);

        // Hitung persentase
        $onTimePercent = $totalHariKerja > 0 ? round(($onTime / $totalHariKerja) * 100, 1) : 0;
        $latePercent   = $totalHariKerja > 0 ? round(($late / $totalHariKerja) * 100, 1) : 0;
        $alphaPercent  = $totalHariKerja > 0 ? round(($alpha / $totalHariKerja) * 100, 1) : 0;

        // Total jam kerja hari ini
        $todayHours = Attendance::where('employee_id', $employeeId)
            ->where('status', 0)
            ->whereDate('attendance_date', $today)
            ->get()
            ->sum(function ($attendance) {
                if ($attendance->status != 0) {
                    return 0; // pastikan tidak dihitung
                }
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : now();
                return $checkIn->diffInMinutes($checkOut) / 60;
            });
        if($todayHours->status ===0){
             $hours = floor($todayHours);
            $minutes = round(($todayHours - $hours) * 60);
            $formattedToday = "{$hours} jam {$minutes} menit";
        }else{
            // $hours = floor($todayHours);
            // $minutes = round(($todayHours - $hours) * 60);
            $formattedToday = "0 jam 0 menit";
        }
        

        return [
            Stat::make('On Time ' . $periodName, "{$onTimePercent}%")
                ->description("{$onTime} dari {$totalHariKerja} hari kerja hadir tepat waktu")
                ->color('success'),

            Stat::make('Late ' . $periodName, "{$latePercent}%")
                ->description("{$late} dari {$totalHariKerja} hari kerja datang terlambat")
                ->color('warning'),

            Stat::make('Alpha ' . $periodName, "{$alphaPercent}%")
                ->description("{$alpha} dari {$totalHariKerja} hari kerja tanpa kehadiran")
                ->color('danger'),

            Stat::make('Hari Ini', $formattedToday)
                ->description('Total jam kerja hari ini')
                ->color('info'),
        ];
    }
}