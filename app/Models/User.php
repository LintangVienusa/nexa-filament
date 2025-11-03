<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $connection = 'mysql';
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }
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

    

    public function employee()
    {
        return $this->setConnection('mysql_employees')
            ->hasOne(Employee::class, 'email', 'email');
    }


    public function isManager(): bool
    {
        return $this->employee && strtolower($this->employee->job_title) === 'manager';
    }

    public function isStaff(): bool
    {
        return $this->employee && strtolower($this->employee->job_title) === 'staff';
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->employee && $this->employee->file_photo
        ? asset('storage/' . $this->employee->file_photo)
        : null;

        // return null; // Filament pakai avatar default kalau foto kosong
    }

    public function getFilamentName(): string
    {
        return $this->employee?->full_name ?? $this->name ?? $this->email;
    }
}
