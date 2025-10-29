<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Resources\AttendanceReportResource\Widgets\ReportAttendanceWidget;

class AttedanceDashboard extends Page
{
    
    protected static ?string $title = 'Attendance Report';
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'HR Management';

    protected static string $view = 'filament.pages.attedance-dashboard';
     protected function getHeaderWidgets(): array
    {
        return [
            ReportAttendanceWidget::class,
        ];
    }
}
