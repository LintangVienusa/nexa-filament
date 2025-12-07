<?php

namespace App\Filament\Resources\LeaveResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Leave;
use App\Models\Employee;
use Carbon\Carbon;

class LeaveSummary extends BaseWidget
{
    protected function getStats(): array
    {
        $year = Carbon::now()->year;
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->first();

        $joinDate = Carbon::parse($employee->date_of_joining);
        $now = Carbon::now();

        $yearsWorked = $joinDate->diffInYears($now);

        $cutiPerYear = 12;

        $totalCutiHak = $yearsWorked >= 1 ? $cutiPerYear : 0;

        if($totalCutiHak > 0){
            $cutiDiambil = Leave::where('employee_id', $employee->employee_id)
                    ->where('leave_type', '1')
                    ->whereIn('status', ['2','4','6'])   
                    ->whereYear('start_date', $year)
                    ->sum('leave_duration');
            $cutiSisa = $totalCutiHak - $cutiDiambil;
        }else{
            $cutiDiambil = $totalCutiHak;
            $cutiSisa = $totalCutiHak;
        }
        
        $totalcutilain = Leave::where('employee_id', $employee->employee_id)
            ->whereIn('leave_type', ['2','3','4','5','6','7']) 
            ->whereIn('status', ['2','4','6'])   
            ->whereYear('start_date', $year)
            ->sum('leave_duration');
        

        return [
            Stat::make('Total Saldo Cuti Tahunan', $cutiSisa)
                ->description('Hari')
                ->color('info'),

            Stat::make('Total Cuti yang Diambil Tahunan', $cutiDiambil)
                ->description('Hari')
                ->color('success'),

            Stat::make('Izin Tahun Ini', $totalcutilain)
                ->description('Hari')
                ->color('success'),
        ];
    }
}
