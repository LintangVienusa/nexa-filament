<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;

class Payroll extends Model
{
     protected $connection = 'mysql_employees';
     protected $table = 'Payrolls';

     protected $fillable = [
        'employee_id',
        'periode',
        'status',
        'number_of_employees',
        'start_date',
        'cutoff_date',
        'salary_slip_number',
        'salary_slips_created',
        'salary_slips_approved',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'start_date' => 'date',
        'cutoff_date' => 'date',
    ];

      public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function salarySlips()
    {
        return $this->hasMany(SalarySlip::class, 'payroll_id');
    }

    

     protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->employee ? trim("{$this->employee->first_name} {$this->employee->last_name}") : null;
    }

     protected static function booted()
    {
        static::saving(function ($payroll) {
            // jika periode diisi tapi start_date & cutoff_date belum diisi atau berubah
            if (!empty($payroll->periode)) {
                try {
                    $date = Carbon::createFromFormat('F Y', $payroll->periode);
                    $payroll->start_date = $date->copy()->startOfMonth()->toDateString();
                    $payroll->cutoff_date = $date->copy()->endOfMonth()->toDateString();
                } catch (\Exception $e) {
                    // jika format salah, biarkan saja (biar error validation menangani)
                }
            } else {
                // fallback: jika tidak ada periode, pakai bulan sekarang
                $now = Carbon::now();
                $payroll->periode = $now->format('F Y');
                $payroll->start_date = $now->startOfMonth()->toDateString();
                $payroll->cutoff_date = $now->endOfMonth()->toDateString();
            }

            $calc = self::recalculate($payroll->toArray());
            // $payroll->gross_salary = $calc['gross_salary'];
            $payroll->salary_slips_created   = $calc['salary_slips_created'];
        });
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['basic_salary'] = self::getBasicSalary($data['employee_id']);
        $data['overtime'] = self::calculateOvertime($data['employee_id'], $data['period_start'], $data['period_end']);
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['basic_salary'] = self::getBasicSalary($data['employee_id']);
        $data['overtime'] = self::calculateOvertime($data['employee_id'], $data['period_start'], $data['period_end']);
        return $data;
    }

    public static function calculateOvertime($employeeId, $start, $end): int
    {
        $startDate = Carbon::parse($start)->format('Y-m-d');
        $endDate   = Carbon::parse($end)->format('Y-m-d');

        $overtimes = DB::connection('mysql_employees')
            ->table('overtimes as o')
            ->join('attendances as a', 'o.attendance_id', '=', 'a.id')
            ->where('o.employee_id', $employeeId)
            ->whereBetween('a.attendance_date', [$startDate, $endDate])
            ->select('o.working_hours', 'a.attendance_date')
            ->get();

        $total = 0;

        foreach ($overtimes as $ot) {
            $date = Carbon::parse($ot->attendance_date);
            $day = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $rate = in_array($day, [0,6]) ? 60000 : 30000;

            $total += $ot->working_hours * $rate;
        }

        return round(max(0, $total));
    }

    public static  function recalculate(array $data)
    {
        
        $basic = (float) ($data['basic_salary'] ?? 0);
        $allow = (float) ($data['allowances'] ?? 0);
        $overtime = (float) ($data['overtime_pay'] ?? 0);
        $bonus = (float) ($data['bonus'] ?? 0);
        $deduct = (float) ($data['deductions'] ?? 0);

        $gross = $basic + $allow + $overtime + $bonus;
        $net = $gross - $deduct;

        return [
            // 'gross_salary' => $gross,
            'salary_slips_created'   => $net,
        ];
    
    }

    

    
}
