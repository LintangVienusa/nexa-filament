<?php

namespace App\Filament\Resources\AttendanceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceLateWidget extends BaseWidget
{
    protected  ?string $heading = 'Total Kehadiran Seluruh Karyawan';
    protected function getStats(): array
    {
        if (!Auth::user()->hasRole('superadmin')) {
            return [];
        }
        $today = Carbon::today();

        if ($today->day >= 28) {
            $startPeriod = $today->copy()->day(28)->startOfDay();
            $endPeriod = $today->copy()->addMonthNoOverflow()->day(27)->endOfDay();
        } else {
            $startPeriod = $today->copy()->subMonthNoOverflow()->day(28)->startOfDay();

            if ($today->day < 27) {
                $endPeriod = $today->copy()->endOfDay();
            } else {
                $endPeriod = $today->copy()->day(27)->endOfDay();
            }
        }

        $attendances = Attendance::whereBetween('attendance_date', [$startPeriod, $endPeriod])->get();

        $totalHariKerja = $attendances->groupBy('attendance_date')->count();
        $late = $attendances->where('status', 2)->count();
        $absent = $attendances->whereIn('status', [0, 1])->count();
        $latePercent = $totalHariKerja > 0 ? round(($late / $attendances->count()) * 100, 1) : 0;
        $absentPercent = $totalHariKerja > 0 ? round(($absent / $attendances->count()) * 100, 1) : 0;

        return [
            Stat::make('Present Summary', "{$absentPercent}%")
                ->description("{$absent} dari {$attendances->count()} absensi datang tepat waktu")
                ->color('success'),

            Stat::make('Late Summary', "{$latePercent}%")
                ->description("{$late} dari {$attendances->count()} absensi datang terlambat")
                ->color('warning')
                ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'onclick' => "window.location.href='" . \App\Filament\Resources\AttendanceResource::getUrl('late-list') . "'",
                    ]),
        ];
    }
}
