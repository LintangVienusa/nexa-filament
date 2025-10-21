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
    
    protected static ?string $title = 'Transaksi Asset';

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     \Log::info('Form State', $data);
    //     return $data;
    // }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $assetTransaction = static::getModel()::create($data);

            if (!empty($data['requested_items'])) {
                 
                foreach ($data['requested_items'] as $item) {
                    if ($assetTransaction->transaction_type === 'RELEASE') {

                        $asset = Assets::find($item['asset_id']);
                        if (!$asset) {
                            \Log::warning('Asset not found for RELEASE', ['item' => $item]);
                            continue;
                        }

                        $nextMoveId = (AssetMovement::max('id') ?? 0) + 1;

                        \Log::info('Usage type:', ['usage_type' => $data['usage_type'] ?? null]);
                       
                        $movement = AssetMovement::create([
                            'movement_id'         => 'MOV' . str_pad($nextMoveId, 5, '0', STR_PAD_LEFT),
                            'asset_transaction_id'=> $assetTransaction->id,
                            'asset_id'            => $asset->id,
                            'movementType'        => 'OUT',
                            'movementDate'        => now()->toDateString(),
                            'deployment_date'     => now()->toDateString(),
                            'PIC'                 => $data['PIC'],
                            'serialNumber'        => $item['serialNumber'] ?? null,
                            'placement_type'      => $data['usage_type'] ?? '',
                            'assigned_to'         => $data['assigned_id'] ?? null,
                            'recipient'           => $data['recipient_by'] ?? null,
                            'sender'              =>  null,
                            'location'            => $this->getDeploymentLocation($data),
                            'province_code'         => $data['province_code'] ?? null,
                            'regency_code'           => $data['regency_code'] ?? null,
                            'village_code'           => $data['village_code'] ?? null,
                            'recipient'           => $data['recipient_by'] ?? null,
                            'created_by'          => auth()->user()->name,
                            'status'              => 0,
                        ]);

                        AssetTransactionItem::create([
                            'asset_transaction_id' => $assetTransaction->id,
                            'asset_id'             => $asset->id,
                            'item_code'            => $asset->item_code,
                            'merk'                 => $asset->merk,
                            'type'                 => $asset->type,
                            'serial_number'        => $asset->serialNumber,
                            'description'          => $asset->description,
                            'movement_id'          => $movement->id,
                        ]);

                        

                        $asset->status = 1; 
                        $asset->save();

                    } else {

                        $category = \App\Models\CategoryAsset::find($data['category_id']);
                        $nextId = (Assets::max('id') ?? 0) + 1;
                        $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                        $itemCode = ($category?->category_code ?? 'CAT') . $formattedId;

                        $asset = Assets::create([
                            'item_code'       => $itemCode,
                            'name'            => $item['name'] ?? 'Unknown',
                            'type'            => $item['type'] ?? null,
                            'merk'            => $item['merk'] ?? null,
                            'serialNumber'    => $item['serialNumber'] ?? null,
                            'category_id'     => $data['category_id'],
                            'description'     => $item['notes'] ?? null,
                            'asset_condition' => $item['asset_condition'] ?? 'GOOD',
                            'notes'           => $item['notes'] ?? null,
                            'status'          => 0,
                            'created_by'      => auth()->user()->email,
                        ]);

                         if($data['sender_by'] === "other"){
                            $sender = $data['sender_custom'];
                        }else{
                            $sender =$data['sender_by'];
                        }

                        if($data['usage_type'] === "RETURN WAREHOUSE"){
                            $return = now()->toDateString();
                            $returned_by = $sender;
                            $received_by = $data['recipient_by'];
                        }

                        $nextMoveId = (AssetMovement::max('id') ?? 0) + 1;
                        $movement = AssetMovement::create([
                            'movement_id'         => 'MOV' . str_pad($nextMoveId, 5, '0', STR_PAD_LEFT),
                            'asset_transaction_id'=> $assetTransaction->id,
                            'asset_id'            => $asset->id,
                            'movementType'        => 'IN',
                            'movementDate'        => now()->toDateString(),
                            'PIC'                 => $data['PIC'],
                            'notes'               => $data['notes'] ?? null,
                            'placement_type'      => 'STOCK IN WAREHOUSE',
                            'assigned_to'         => $data['assigned_id'] ?? null,
                            'recipient'           => $data['recipient_by'] ?? null,
                            'sender'              => $sender ?? null,
                            'returned_by'         => $returned_by ?? null,
                            'received_by'         => $received_by ?? null,
                            'return_date'         => $return ?? null,
                            'location'            => $this->getDeploymentLocation($data),
                            'province_code'         => $data['province_code'] ?? null,
                            'regency_code'           => $data['regency_code'] ?? null,
                            'village_code'           => $data['village_code'] ?? null,
                            'created_by'          => auth()->user()->name,
                            'status'              => 0,
                        ]);

                        AssetTransactionItem::create([
                            'asset_transaction_id' => $assetTransaction->id,
                            'asset_id'             => $asset->id,
                            'item_code'            => $asset->item_code,
                            'merk'                 => $asset->merk,
                            'type'                 => $asset->type,
                            'serial_number'        => $asset->serialNumber,
                            'description'          => $asset->description,
                            'movement_id'          => $movement->id,
                        ]);

                        
                    }
                }

                if ($assetTransaction->transaction_type === 'RELEASE') {
                        $inventory = InventoryAsset::where('categoryasset_id', $data['category_id'])->first();
                                if ($inventory) {
                                    $inventory->inWarehouse -= $data['request_asset_qty'];
                                    $inventory->outWarehouse += $data['request_asset_qty'];
                                    $inventory->save();
                                }
                    }else{
                        $inventory = InventoryAsset::where('categoryasset_id', $data['category_id'])->first();
                        if ($inventory) {
                            $inventory->inWarehouse += $data['request_asset_qty'];
                            $inventory->save();
                        }
                    }
            }

            return $assetTransaction;
        });
    }

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
