<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $connection = 'mysql'; // DB nexa_filament
    protected $table = 'Users';

    public function employee()
    {
        return $this->setConnection('mysql_employees')
            ->belongsTo(Employee::class, 'email', 'email');
    }

    
    public function isManager(): bool
    {
        return $this->employee && strtolower($this->employee->job_title) === 'manager';
    }

    public function isStaff(): bool
    {
        return $this->employee && strtolower($this->employee->job_title) === 'staff';
    }
}
