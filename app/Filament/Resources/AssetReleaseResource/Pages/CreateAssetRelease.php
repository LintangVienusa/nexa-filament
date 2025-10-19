<?php

namespace App\Filament\Resources\AssetReleaseResource\Pages;

use App\Filament\Resources\AssetReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use App\Models\AssetMovement;
use App\Models\AssetReleaseItem;
use App\Models\Assets;


class CreateAssetRelease extends CreateRecord
{
    protected static string $resource = AssetReleaseResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // 1️⃣ Simpan record utama Asset Release
            $assetRelease = static::getModel()::create($data);

            // 2️⃣ Insert setiap requested_items ke tabel assetreleaseitems
            if (!empty($data['requested_items'])) {
                foreach ($data['requested_items'] as $item) {

                    // 3️⃣ Simpan ke Asset Movement (karena ada kolom movement_id di items)
                    $nextId = (AssetMovement::max('id') ?? 0) + 1;
                    $movement = AssetMovement::create([
                        'asset_movement_id' => 'MOV' . str_pad($nextId, 5, '0', STR_PAD_LEFT),
                        'asset_id'         => $item['asset_id'],
                        'movementType'     => 'OUT',
                        'movementDate'     => now()->toDateString(),
                        'serialNumber'     => $item['serialNumber'] ?? '',
                        'PIC'              => $data['PIC'],
                        'notes'            => $data['ba_description'] ?? null,
                        'placement_type'   => $data['usage_type'] ?? 'WAREHOUSE',
                        'assigned_to'      => $data['assigned_id'] ?? null,
                        'location'         => $this->getDeploymentLocation($data),
                        'created_by'       => auth()->user()->name,
                        'status'           => 0,
                    ]);

                    // 4️⃣ Simpan ke Asset Release Items
                    AssetReleaseItem::create([
                        'asset_release_id' => $assetRelease->id,
                        'asset_id'        => $item['asset_id'],
                        'item_code'       => $item['item_code'] ?? null,
                        'merk'           => $item['merk'] ?? null,
                        'type'           => $item['type'] ?? null,
                        'serial_number'  => $item['serialNumber'] ?? null,
                        'description'    => $item['description'] ?? null,
                        'movement_id'    => $movement->id, // relasi ke assetmovement
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            return $assetRelease;
        });
    }

    /**
     * Utility untuk gabungkan lokasi berdasarkan kode provinsi, kabupaten, desa.
     */
    private function getDeploymentLocation(array $data): ?string
    {
        if (($data['usage_type'] ?? null) !== 'DEPLOYED_FIELD') {
            return null;
        }

        return collect([
            $data['province_code'] ?? null,
            $data['district_code'] ?? null,
            $data['village_code'] ?? null,
        ])->filter()->implode(' - ');
    }
}
