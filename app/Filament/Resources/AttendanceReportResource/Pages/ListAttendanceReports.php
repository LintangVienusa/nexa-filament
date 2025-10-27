<?php

namespace App\Filament\Resources\AttendanceReportResource\Pages;

use App\Filament\Resources\AttendanceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AttendanceReportResource\Widgets\ReportAttendanceWidget;

class ListAttendanceReports extends ListRecords
{
    protected static string $resource = AttendanceReportResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ReportAttendanceWidget::class,
        ];
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }
    // Sembunyikan table
    protected function getTableColumns(): array
    {
        return [];
    }
}
