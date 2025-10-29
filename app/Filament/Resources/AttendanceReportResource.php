<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\AttendanceReportResource\Pages;
// use App\Filament\Resources\AttendanceReportResource\RelationManagers;
// use App\Models\AttendanceReport;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use App\Filament\Resources\AttendanceReportResource\Widgets\ReportAttendanceWidget;
// use App\Models\Employee;
// use App\Models\Organization;
// use App\Services\HariKerjaService;
// use Carbon\Carbon;
// use Filament\Tables\Filters\Filter;
// use Filament\Tables\Filters\SelectFilter;



// class AttendanceReportResource extends Resource
// {
//     protected static ?string $model = AttendanceReport::class;

//     protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
   
//     public static function getWidgets(): array
//     {
//         // Panggil widget untuk menampilkan semua report
//         return [
//             ReportAttendanceWidget::class,
//         ];
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListAttendanceReports::route('/'),
            
//         // 'attendance' => Pages\AttendanceDashboard::route('/attendance-dashboard'),
//             // 'create' => Pages\CreateAttendanceReport::route('/create'),
//             // 'edit' => Pages\EditAttendanceReport::route('/{record}/edit'),
//         ];
//     }
     
// }
