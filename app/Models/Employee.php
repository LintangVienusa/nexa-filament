<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $connection = 'mysql_employees';
    protected $table = 'Employees';

    protected $fillable = [
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'mobile_no',
        'ktp_no',
        'bpjs_kes_no',
        'bpjs_tk_no',
        'npwp_no',
        'address',
        'religion',
        'marital_status',
        'job_title',
        'org_id',
        'bank_account_name',
        'bank_account_no'
    ];
}
