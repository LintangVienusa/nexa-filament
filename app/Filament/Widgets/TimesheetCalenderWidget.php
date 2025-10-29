<?php

namespace App\Filament\Widgets;

use App\Models\Timesheet;

use Guava\Calendar\Widgets\CalendarWidget;
use Illuminate\Support\Collection;
use App\Models\Timesheets;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;

class TimesheetCalenderWidget extends CalendarWidget
{
    // protected static string $view = 'filament.widgets.timesheet-calender-widget';
    public function getEvents(array $fetchInfo = []): Collection | array
    {
       return Timesheet::query()
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get()
            ->map(function ($timesheet) {
                $color = match ((string) $timesheet->status) {
                    '2' => '#16a34a', // Done → hijau
                    '1' => '#f97316', // On Progress → oranye
                    '0' => '#dc2626', // Pending → merah
                    default => '#6b7280', // Default → abu-abu
                };

                return [
                    'id'    => $timesheet->id,
                    'title' => $timesheet->job_description ?? 'No Description',
                    'start' => $timesheet->created_at,
                    'end'   => $timesheet->updated_at,
                    'color' => $color,
                ];
            });
    }
}
