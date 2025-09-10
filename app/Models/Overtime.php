<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Overtime extends Model
{
    //
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
        'start_time',
        'end_time',
        'working_hours',
        'job_id',
    ];

    // Relasi ke Attendance
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }

    // Relasi ke Job
    public function jobs()
    {
        return $this->belongsTo(Jobs::class, 'job_id', 'id');
    }

    

    protected static function booted()
    {
        static::saving(function ($overtime) {
            // hanya hitung jika start & end ada
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


}
