<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = config('permissions.resources', []);
        $actions   = config('permissions.actions', []);

        foreach ($resources as $key => $label) {
            foreach ($actions as $action) {
                $name = "{$key}.{$action}";
                Permission::firstOrCreate(['name' => $name]);
            }
        }
    }
}
