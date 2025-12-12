<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;
    public array $collectedPermissions = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = $this->record->permissions->pluck('name')->toArray();

        $resources = \App\Helpers\FilamentHelper::getResources();

        foreach ($resources as $key => $label) {
            $data["permissions_{$key}"] = array_filter($permissions, function ($perm) use ($key) {
                return str_contains($perm, $key);
            });
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->collectedPermissions = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $this->collectedPermissions = array_merge($this->collectedPermissions, $value);
            }
        }

        return [
            'name' => $data['name'],
        ];
    }

    protected function afterSave(): void
    {
        foreach ($this->collectedPermissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $this->record->syncPermissions($this->collectedPermissions);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}
