<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $connection = 'mysql_employees';
    protected $table = 'Employees';
    protected $primaryKey = 'employee_id';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'mobile_no',
        'email',
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

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class, 'email', 'email');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_id');
    }
}
