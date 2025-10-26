<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasOwnRecordPolicy;
use Carbon\Carbon;
use App\Filament\Resources\AttendanceResource\Widgets\AttendanceSummary;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;
    
    

    protected function getHeaderActions(): array
    {
        $today = Carbon::today()->toDateString();
        $employee = Employee::where('email',auth()->user()->email)->first();
         $employeeId = $employee->employee_id;

        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->first();

        

        if (!$attendance) {
            $label = 'Check In';
            $color = 'success';
            // $icon = 'heroicon-o-login';
            
            $route = AttendanceResource::getUrl('create');
        } elseif (!$attendance->check_out_latitude) {

            $hasOnProgressTimesheet =Timesheet::where('attendance_id', $attendance->id)
            ->where('status', '0')
            ->exists();

            $jobTimesheet = Timesheet::where('attendance_id', $attendance->id)->first();
            $jobTimeId = isset($jobTimesheet->id) ? $jobTimesheet->id : null;

            if(!$hasOnProgressTimesheet && $jobTimeId !=''){
                        $label = 'Check Out';
                        $color = 'warning';
                        // $icon = 'heroicon-o-logout';
                        $route = AttendanceResource::getUrl('edit', ['record' => $attendance->id]);
            }else{
                $label = 'Create Job';
                $color = 'warning';
                // $icon = 'heroicon-o-logout';
                $route = TimesheetResource::getUrl('create');
            }
    
        } else {
            return []; 
        }

        return [
            Action::make('today_action')
                ->label($label)
                ->color($color)
                ->url($route)
                ->requiresConfirmation(), 
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceSummary::class,
        ];
    }
    
    // protected function getFooterWidgets(): array
    // {
        
    // }
}
