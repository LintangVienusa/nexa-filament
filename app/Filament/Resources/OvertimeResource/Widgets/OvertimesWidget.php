<?php

namespace App\Filament\Resources\OvertimeResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OvertimesWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $todayf = Carbon::now('Asia/Jakarta');

        
        $employee = Auth::user()->employee;
        $employeeId = $employee?->employee_id;
        
        if ($todayf->day >= 28) {
            $startPeriod = $todayf->copy()->day(28)->startOfDay();
            $endPeriod = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
        } else {
            $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
            $endPeriod = $todayf->copy()->day(27)->endOfDay();
        }

        $totalOvertimes = Overtime::where('Overtimes.employee_id', $employeeId)
                ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
                ->count();

        $overtimes = Overtime::where('Overtimes.employee_id', $employeeId)
            ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
            ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
            ->get(['Overtimes.start_time', 'Overtimes.end_time',]);

       $totalOvertime = $overtimes->sum(function ($overtime) {
                $start = Carbon::parse($overtime->start_time);
                $end = $overtime->end_time ? Carbon::parse($overtime->end_time) : now();
                return $start->floatDiffInHours($end);
            });

            $hours = floor($totalOvertime);
            $minutes = round(($totalOvertime - $hours) * 60);
            $formatted = "{$hours} jam {$minutes} menit";

        return [
            Stat::make('Total Overtimes', $totalOvertimes) 
                ->description('Jumlah overtime dibuat dalam periode 28-bulan-kemarin s/d 27-bulan-ini')
                ->color('success'),

             Stat::make('Total Jam ',$formatted)
                ->description('Semua data overtimes')
                ->color('success'),
        ];
    }
}
