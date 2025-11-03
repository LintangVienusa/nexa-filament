<?php

namespace App\Filament\Resources\TimesheetResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Timesheet;

class TimesheetPendingChart extends ChartWidget
{
    protected static ?string $heading = 'Pending';
    protected static ?string $maxHeight = '180px'; // biar seragam

    protected function getData(): array
    {
        $stats = Timesheet::selectRaw("
            COUNT(*) AS total,
            SUM(status = '1') AS pending
        ")
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->first();

        $total = $stats->total ?: 1;
        $pending = round(($stats->pending / $total) * 100, 2);

        return [
            'datasets' => [[
                'data' => [$pending, 100 - $pending],
                'backgroundColor' => ['#f97316', '#e5e7eb'],
                'borderWidth' => 0,
                'hoverOffset' => 6,
            ]],
            'labels' => ["{$pending}%"],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '75%', // lubang tengah
            'plugins' => [
                'legend' => ['display' => false], // ❌ tanpa legend
                'tooltip' => ['enabled' => false], // ❌ tanpa tooltip
                'datalabels' => [
                    'display' => true,
                    'color' => '#000',
                    'font' => ['weight' => 'bold', 'size' => 16],
                    'formatter' => \Illuminate\Support\Js::from("
                        function(value, ctx) {
                            if (ctx.dataIndex === 0) {
                                return value + '%';
                            }
                            return '';
                        }
                    "),
                ],
            ],
            // ❌ Nonaktifkan semua garis / axis
            'scales' => [
                'x' => ['display' => false, 'grid' => ['display' => false, 'drawBorder' => false]],
                'y' => ['display' => false, 'grid' => ['display' => false, 'drawBorder' => false]],
            ],
        ];
    }

    protected function getScripts(): array
    {
        return ['https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2'];
    }
}
