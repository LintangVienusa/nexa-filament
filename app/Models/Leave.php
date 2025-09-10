<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Leave extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'Leaves';

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
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'leave_duration',
        'reason',
        'status',
        'approved_by',
        'note_rejected',
        'leave_evidence',
        'created_at',
        'updated_at',

    ];



    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->employee ? trim("{$this->employee->first_name} {$this->employee->last_name}") : null;
    }

    public $timestamps = true;

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($leave) {
            if (auth()->check() && auth()->user()->employee?->job_title !== 'Manager') {
                $leave->status = 0; // submit
            }

            if ($leave->start_date && $leave->end_date) {
                $start = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);
                $end   = $leave->end_date instanceof Carbon ? $leave->end_date : Carbon::parse($leave->end_date);
                $leave->leave_duration = $start->diffInDays($end) + 1;
            }
        });



        static::updating(function ($leave) {
            if ($leave->start_date && $leave->end_date) {
                    $start = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);
                    $end   = $leave->end_date instanceof Carbon ? $leave->end_date : Carbon::parse($leave->end_date);
                    $leave->leave_duration = $start->diffInDays($end) + 1;
                }
        });

        static::saving(function ($leave) {
            if ($leave->leave_type === '4' && empty($leave->leave_evidence)) {
                throw new \Exception('Evidence wajib diisi untuk cuti sakit.');
            }
        });
    }







}
