<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceReportResource\Pages;
use App\Filament\Resources\AttendanceReportResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceReportResource\Widgets\ReportAttendanceWidget;


class AttendanceReportResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             //
    //         ]);
    // }
//     public static function getHeaderWidgets(): array
// {
//     return [
//         ReportAttendanceWidget::class,
//     ];
// }

    // public static function table(Table $table): Table
    // {
    //     return $table
    //         ->columns([
    //             //
    //         ])
    //         ->filters([
    //             //
    //         ])
    //         ->actions([
               
    //         ])
    //         ->bulkActions([
               
    //         ])->pagination(false);
    // }

    // public static function getRelations(): array
    // {
    //     return [
    //         //
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceReports::route('/'),
            // 'create' => Pages\CreateAttendanceReport::route('/create'),
            // 'edit' => Pages\EditAttendanceReport::route('/{record}/edit'),
        ];
    }
     
}
