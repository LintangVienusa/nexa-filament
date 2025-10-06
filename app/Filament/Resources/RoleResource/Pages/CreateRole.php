<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $permissions = [];

        foreach ($data as $key => $value) {
            if (Str::startsWith($key, 'permissions_') && is_array($value)) {
                $permissions = array_merge($permissions, $value);
                unset($data[$key]);
            }
        }

        $this->permissions = $permissions;
        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->permissions)) {
            foreach ($this->permissions as $permissionName) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web', // sesuaikan kalau kamu pakai guard lain
                ]);
            }

            $this->record->syncPermissions($this->permissions);
        }
    }
}
