<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Leave extends Model
{

    
    use HasFactory, LogsActivity;
    
    protected $connection = 'mysql_employees';
    protected $table = 'Leaves';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id',
                'leave_type',
                'start_date',
                'end_date',
                'leave_duration',
                'reason',
                'created_by',
                'status',
            ])
            ->logOnlyDirty()
            ->useLogName('Leaves');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->properties = $activity->properties->merge([
            'ip'    => request()->ip(),
            'menu'  => 'Leaves',
            'email' => $user?->email,
            'record_id' => $this->id,
            'Leaves' => $this->id,
            'status' => $this->status,
        ]);

        $activity->email = $user?->email;
        $activity->menu  = 'Leaves';
    }

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
        'created_by',
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
             $user = auth()->user();
            if (auth()->check() && auth()->user()->employee?->job_title !== 'Manager') {
                $leave->status = 0;
                $leave->created_by = $user->employee?->email;
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
            $user = auth()->user();
            
            if ($leave->leave_type === '2' && empty($leave->leave_evidence)) {
                throw new \Exception('Evidence wajib diisi untuk cuti sakit.');
            }else{
                if ($leave->status == 2 && $leave->exists) {
                    $leave->approved_by = $user->employee?->email;
                    $leave->updated_at = now(); 
                }
            }

            if ($leave->status == 3) { 
                $leave->approved_by = $user->employee?->email;
                $leave->note_rejected = $leave->note_rejected; 
                $leave->updated_at = now(); 
            }
        });
    }

    public static function getAnnualLeaveBalance($employeeId)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return 0;
        }

        $dateJoin = Carbon::parse($employee->date_of_joining);
        $now = Carbon::now();

        if ($dateJoin->diffInYears($now) < 1) {
               session()->flash('info', 'Employee belum genap 1 tahun bekerja. Cuti tahunan mungkin terbatas.');
    
            return 0;
        }else{
            $quota = 12;

            $used = self::where('employee_id', $employeeId)
                ->where('leave_type', 1) 
                ->where('status', 2)    
                ->whereYear('start_date', $now->year)
                ->sum('leave_duration');

            return max($quota - $used, 0);
        }

        
    }

    public static function getMarriageLeaveBalance($employeeId)
    {
        $used = self::where('employee_id', $employeeId)
            ->where('leave_type', 7) 
            ->where('status', 2)
            ->count(); 

        return $used > 0 ? 0 : 3;
    }

    public static function getMaternityLeaveBalance($employeeId)
    {
        $now = Carbon::now();

        $used = self::where('employee_id', $employeeId)
            ->where('leave_type', 7) 
            ->where('status', 2)
            ->whereYear('start_date', $now->year) 
            ->sum('leave_duration');

        return max(90 - $used, 0);
    }

    public function getLeaveEvidenceUrlAttribute()
    {
        return $this->leave_evidence 
            ? asset('/storage/leave-evidence/' . $this->leave_evidence) 
            : null;
    }

    






}
