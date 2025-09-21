<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Job;

class Overtime extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'Overtimes';

    public function user()
    {
        return $this->setConnection('mysql_employees')
            ->hasOne(Employee::class, 'email', 'email');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    protected $fillable = [
        'attendance_id',
        'employee_id',
        'start_time',
        'end_time',
        'working_hours',
        'description',
        'job_id',
        'created_by',
        'updated_by'
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->employee ? trim("{$this->employee->first_name} {$this->employee->last_name}") : null;
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }

    protected static function booted()
    {
        static::saving(function ($overtime) {
            if ($overtime->start_time && $overtime->end_time) {
                $start = Carbon::parse($overtime->start_time);
                $end   = Carbon::parse($overtime->end_time);

                if ($end->lessThan($start)) {
                    $end->addDay();
                }

                $minutes = $start->diffInMinutes($end);
                $hours = round($minutes / 60, 2);

                $overtime->working_hours = $hours;
            }
        });
    }

    public static function getLatestAttendanceId()
    {
        $employeeId = auth()->user()?->employee?->employee_id;

        if (!$employeeId) {
            return null;
        }

        return DB::connection('mysql_employees')
            ->table('Attendances')
            ->where('employee_id', $employeeId)
            ->latest('id')
            ->value('id');
    }
}
