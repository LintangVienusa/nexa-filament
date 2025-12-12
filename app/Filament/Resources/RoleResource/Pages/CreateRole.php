<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
    protected array $collectedPermissions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
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
