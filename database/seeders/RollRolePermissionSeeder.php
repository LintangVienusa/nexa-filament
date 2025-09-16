<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Employees
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',

            // Attendances
            'view attendances',
            'create attendances',
            'edit attendances',
            'delete attendances',

            // Leaves
            'view leaves',
            'create leaves',
            'edit leaves',
            'delete leaves',

            // Overtimes
            'view overtimes',
            'create overtimes',
            'edit overtimes',
            'delete overtimes',

            // Organizations
            'view organizations',
            'create organizations',
            'edit organizations',
            'delete organizations',

            // Salary Components
            'view salary components',
            'create salary components',
            'edit salary components',
            'delete salary components'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Role: Admin (all access)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Role: HR Manager
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view attendances',
            'create attendances',
            'edit attendances',
            'view leaves',
            'create leaves',
            'edit leaves',
            'delete leaves',
            'view overtimes',
            'create overtimes',
            'edit overtimes',
            'delete overtimes',
            'view organizations',
            'edit organizations',
            'delete organizations',
            'view salary components',
            'create salary components',
            'edit salary components',
            'delete salary components'
        ]);

        // Role: Employee
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->givePermissionTo([
            'view employees',
            'view organizations',
            'view attendances',
            'create attendances',
            'edit attendances',
            'view leaves',
            'create leaves',
            'edit leaves',
            'view overtimes',
            'create overtimes',
            'edit overtimes',
        ]);
    }
}