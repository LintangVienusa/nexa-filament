<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['admin', 'manager', 'employee'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::firstOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => config('admin.name'),
                'email_verified_at' => now(),
                'password' => Hash::make(config('admin.password')),
            ]
        );
        $admin->assignRole('admin');

        $manager = User::firstOrCreate(
            ['email' => 'manager@testing.site'],
            [
                'name' => 'Manager User',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
            ]
        );
        $manager->assignRole('manager');

        $employee = User::firstOrCreate(
            ['email' => 'employee@testing.site'],
            [
                'name' => 'Employee User',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
            ]
        );
        $employee->assignRole('employee');
    }
}
