<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\SalaryComponent;

class SalarySlip extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'SalarySlips';
    protected $primaryKey = 'id';

     protected $fillable = [
        'employee_id',
        'payroll_id',
        'salary_component_id',
        'amount',
    ];


    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    protected static function booted()
    {
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
