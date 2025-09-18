<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\SalaryComponent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class SalarySlip extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'SalarySlips';
    protected $primaryKey = 'id';

     protected $fillable = [
        'id',
        'employee_id',
        'periode',
        'payroll_id',
        'salary_component_id',
        'amount',
    ];

    protected $casts = [
        'components' => 'array', 
    ];


    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    protected static function booted()
    {
        parent::boot();
        static::creating(function ($model) {
            $exists = static::where('employee_id', $model->employee_id)
                ->where('salary_component_id', $model->salary_component_id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'salary_component_id' => 'This salary component has already been assigned to the selected employee.',
                ]);
            }
        });

        static::creating(function ($salarySlip) {
            
            $periode = $salarySlip->periode ?? \Carbon\Carbon::now()->format('F Y');
            $startDate = $periode->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $periode->copy()->endOfMonth()->format('Y-m-d');
            $exists = Payroll::where('employee_id', $salarySlip->employee_id)
                ->where('periode', $periode)
                ->first();

                if ($exists) {
                    
                    $salarySlip->payroll_id = $exists->id;
                }else{
                    $payroll = Payroll::create([
                        'employee_id'           => $salarySlip->employee_id,
                        'number_of_employees'   => '0',
                        'periode'               => $salarySlip->periode ?? Carbon::now()->format('F Y'),
                        'start_date'            => $startDate,

                        'cut_off'               => $endDate,
                        'status'                => 0,
                        'created_by'            => Auth::user()->email,
                    ]);

                    $salarySlip->payroll_id = $payroll->id;
                
            }
            // Jika belum ada payroll_id, buat payroll baru
            
        });
    }

     protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->employee ? trim("{$this->employee->first_name} {$this->employee->last_name}") : null;
    }

    public function scopeUniqueEmployee($query)
    {
        return $query->select('employee_id',DB::raw('MAX(id) as id'))
                    ->groupBy('employee_id')
                    ->orderBy('id', 'asc');;
    }



}
