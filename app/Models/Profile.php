<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
   
    protected $connection = 'mysql_employees';
    protected $table = 'Employees';
    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'employee_id',
        'file_photo',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'basic_salary',
        'mobile_no',
        'email',
        'ktp_no',
        'bpjs_kes_no',
        'bpjs_tk_no',
        'npwp_no',
        'address',
        'religion',
        'marital_status',
        'children_count',
        'job_title',
        'org_id',
        'bank_account_name',
        'bank_account_no',
        'name_in_bank_account'
    ];

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organizations::class, 'id', 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class, 'email', 'email');
    }
}
