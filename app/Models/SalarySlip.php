<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\SalaryComponent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;


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
                ->where('periode', $model->periode)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'salary_component_id' => 'This salary component has already been assigned to the selected employee.',
                ]);
            }
        });

        static::creating(function ($salarySlip) {
            
            $periodeCarbon = $salarySlip->periode
                        ? Carbon::createFromFormat('F Y', $salarySlip->periode)
                        : Carbon::now();
             $periodeString = $periodeCarbon->format('F Y');
            // $startDate = $periodeCarbon->copy()->startOfMonth()->format('Y-m-d');
            // $endDate = $periodeCarbon->copy()->endOfMonth()->format('Y-m-d');

            $startDate = $periodeCarbon->copy()->subMonthNoOverflow()->day(28)->format('Y-m-d');
            $endDate = $periodeCarbon->copy()->day(27)->format('Y-m-d');
            
            $payroll = Payroll::where('employee_id', $salarySlip->employee_id)
            ->where('periode', $periodeString)
            ->first();

            if ($payroll) {
                
                $salarySlip->payroll_id = $payroll->id;
                // $payroll->salary_slips_created = ($payroll->salary_slips_created ?? 0) + ($salarySlip->amount ?? 0);
                // $payroll->save();
                
            }else{
                    
                $payroll = Payroll::create([
                    'employee_id'           => $salarySlip->employee_id,
                    'number_of_employees'   => '0',
                    'periode'               => $periodeString ?? Carbon::now()->format('F Y'),
                    'start_date'            => $startDate,
                    'cut_off'               => $endDate,
                    'status'                => 0,
                    'salary_slips_created'  => $salarySlip->amount ?? 0,
                    'created_by'            => Auth::user()->email,
                ]);
                $salarySlip->payroll_id = $payroll->id;
                             
            }
            
            // Total Allowance
            $ta = SalarySlip::where('employee_id', $salarySlip->employee_id)
                ->where('periode', $periodeString)
                ->whereHas('salaryComponent', function ($q) {
                    $q->where('component_type', 0); // Allowance
                })
                ->sum('amount');

            // Total Deduction
            $td = SalarySlip::where('employee_id', $salarySlip->employee_id)
                ->where('periode', $periodeString)
                ->whereHas('salaryComponent', function ($q) {
                    $q->where('component_type', 1); // Deduction
                })
                ->sum('amount');

            $payroll = Payroll::where('employee_id', $salarySlip->employee_id)
            ->where('periode', $periodeString)
            ->first();
            
            if ($payroll) {
                    $total = $ta - $td;
                    DB::connection('mysql_employees')
                        ->table('Payrolls')
                        ->where('id', $payroll->id)
                        ->update(['salary_slips_created' => $total, 'salary_slips_approved' => $total]);
                    // $payroll->salary_slips_created = (int)$ta - (int)$td; // allowance - deduction
                    // $payroll->save();

                    $payroll->refresh();
                    
                }
            
           
            
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

    public static function calculatePph21FromValues(float $basicSalary, float $allowance = 0, float $overtime = 0, int $dependents = 0): float
    {
        $basicSalary = $basicSalary ;
        $allowance = $allowance ?? 0;
        $overtime = $overtime ?? 0;
        $dependents = $employee?->dependents ?? 0;

        $bruto = $basicSalary + $allowance + $overtime;
        $biayaJabatan = min(0.05 * $bruto, 500000);
        $ptkp = 4500000 + ($dependents * 3750000 / 12);
        $pkp = $bruto - $biayaJabatan - $ptkp;

        if ($pkp <= 0) return 0;

        $pkpTahunan = $pkp * 12;
        $tax = 0;

        if ($pkpTahunan <= 60000000) {
            $tax = 0.05 * $pkpTahunan;
        } elseif ($pkpTahunan <= 250000000) {
            $tax = 0.05 * 60000000 + 0.15 * ($pkpTahunan - 60000000);
        } elseif ($pkpTahunan <= 500000000) {
            $tax = 0.05 * 60000000 + 0.15 * (250000000 - 60000000) + 0.25 * ($pkpTahunan - 250000000);
        } else {
            $tax = 0.05 * 60000000 + 0.15 * (250000000 - 60000000) + 0.25 * (500000000 - 250000000) + 0.30 * ($pkpTahunan - 500000000);
        }

        $tax =round($tax / 12);

        return $basic_salary;
    }

    public function calculateNetSalary(): float
    {
        $pph21 = $this->calculatePph21();
        return ($this->basic_salary + $this->allowance + $this->overtime) - $pph21;
    }

    
    
}
