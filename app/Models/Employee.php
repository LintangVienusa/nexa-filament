<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class Employee extends Model
{
    use HasFactory;

    protected $connection = 'mysql_employees';
    protected $table = 'Employees';
    protected $primaryKey = 'employee_id';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'file_photo',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'employe_type',
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

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class, 'email', 'email');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_id');
    }

    public function getFilePhotoUrlAttribute(): ?string
    {
        return $this->file_photo ? asset('storage/' . $this->file_photo) : null;
    }

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    protected static function booted()
    {
        static::created(function ($employee) {
            $middle = $employee->middle_name;
            if($middle ===''){
                $fullname = $employee->first_name.' '.$employee->last_name;
            }else{
                 $fullname = $employee->first_name.' '.$employee->middle_name.' '.$employee->last_name;
            }

            $job_title = $employee->job_title;
            if($job_title == 'Staff'){
                $role = 'employee';
            }elseif($job_title == 'SPV'){
                $role = 'Supervisor';
            } elseif($job_title == 'Manager'){
                $role = 'manager';
            } elseif (in_array($job_title, ['VP', 'CTO', 'CEO'])) {
                $role = 'superadmin';
            }else{
                $role = 'Admin';
            }
            // // $createuser = User::firstOrCreate(
            // //     ['email' => $employee->email],
            // //     [
            // //     'name' => $fullname,
            // //     'email' => $employee->email,
            // //     'password' => Hash::make('n3x4@1234'),
            // // ]);
            // $createuser->assignRole($role);

            $user = User::where('email', $employee->email)->first();

            if (!$user) {
                // Jika belum ada, buat baru
                $user = User::create([
                    'name' => $fullname,
                    'email' => $employee->email,
                    'password' => Hash::make('n3x4@1234'),
                    'role' => $role,
                ]);

                // Assign role untuk Spatie Permission
                $user->assignRole($role);
            } else {
                // Jika sudah ada, update name & role jika job_title berubah
                $user->update([
                    'name' => $fullname,
                    'role' => $role,
                ]);

                // Sync role untuk Spatie Permission
                $user->syncRoles([$role]);
            }
        });
    }

}
