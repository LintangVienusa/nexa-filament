<?php

namespace App\Filament\Resources\AssetTransactionResource\Pages;

use App\Filament\Resources\AssetTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use App\Models\AssetMovement;
use App\Models\AssetTransactionItem;
use App\Models\Assets;
use App\Models\InventoryAsset;

class CreateAssetTransaction extends CreateRecord
{
    protected static string $resource = AssetTransactionResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $AssetTransactions = static::getModel()::create($data);

            if (!empty($data['requested_items'])) {
                foreach ($data['requested_items'] as $item) {

                    $nextId = (AssetMovement::max('id') ?? 0) + 1;
                    $movement = AssetMovement::create([
                        'movement_id' => 'MOV' . str_pad($nextId, 5, '0', STR_PAD_LEFT),
                        'asset_transaction_id' => $AssetTransactions->id,
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

                    AssetTransactionItem::create([
                        'asset_transaction_id' => $AssetTransactions->id,
                        'asset_id'        => $item['asset_id'],
                        'item_code'       => $item['item_code'] ?? null,
                        'merk'           => $item['merk'] ?? null,
                        'type'           => $item['type'] ?? null,
                        'serial_number'  => $item['serialNumber'] ?? null,
                        'description'    => $item['description'] ?? null,
                        'movement_id'    => $movement->id, 
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    $inventory = InventoryAsset::where('categoryasset_id', $data['category_id'])->first();
                    if ($inventory) {
                        if ($AssetTransactions->transaction_type === 'RELEASE') {
                            $inventory->inWarehouse = $inventory->inWarehouse - $data['request_asset_qty'];
                            $inventory->outWarehouse = $inventory->outWarehouse + $data['request_asset_qty'];
                        } else {
                            $inventory->inWarehouse = $inventory->inWarehouse + $data['request_asset_qty'];
                        }
                        $inventory->save();
                    }

                    $asset = Assets::find($item['asset_id']);
                    if ($asset) {
                        if ($AssetTransactions->transaction_type === 'RELEASE') {
                            $asset->status = 1; 
                        } else {
                            $asset->status = 0; 
                        }
                        $asset->save();
                    }
                }
            }

            return $AssetTransactions;
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
