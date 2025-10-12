<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Job;


class Timesheet extends Model
{
    use HasFactory;

    protected $connection = 'mysql_employees';
    protected  $table = 'Timesheets';

    protected $fillable = [
        'attendance_id',
        'job_description',
        'job_duration',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'timesheet_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($timesheet) {
            $user = auth()->user();
            $timesheet->created_by = $user ? $user->email : 'system';
        });

        static::updating(function ($timesheet) {
            $user = auth()->user();
            $timesheet->updated_by = $user ? $user->email : 'system';
        });

        static::created(function (Timesheet $timesheet) {
            Job::create([
                'timesheet_id'    => $timesheet->id,
                'job_description' => $timesheet->job_description,
                'job_duration'    => $timesheet->job_duration,
            ]);
        });
    }
}
