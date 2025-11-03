<?php

namespace App\Filament\Resources\TimesheetResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Timesheet;
use Carbon\Carbon;

class TimesheetStatusChart extends BaseWidget
{
    protected function getHeading(): ?string
    {
        $bulan = Carbon::now()->translatedFormat('F Y');
        return "Task Status – {$bulan}";
    }

    protected function getStats(): array
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;

        // total task bulan ini
        $total = Timesheet::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count() ?: 1;

        // hitung tiap status
        $pending = Timesheet::where('status', '1')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $onProgress = Timesheet::where('status', '0')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $complete = Timesheet::where('status', '2')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        // hitung persentase
        $pendingPercent = round(($pending / $total) * 100, 2);
        $progressPercent = round(($onProgress / $total) * 100, 2);
        $completePercent = round(($complete / $total) * 100, 2);

        return [
            Stat::make('Pending', "{$pendingPercent}%")
                ->description("{$pending} dari {$total} task menunggu")
                ->color('danger'),

            Stat::make('On Progress', "{$progressPercent}%")
                ->description("{$onProgress} dari {$total} task sedang dikerjakan")
                ->color('warning'),

            Stat::make('Complete', "{$completePercent}%")
                ->description("{$complete} dari {$total} task selesai")
                ->color('success'),
        ];
    }
}
