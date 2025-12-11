<?php

namespace App\Filament\Resources\AssetTransactionResource\Pages;

use App\Filament\Resources\AssetTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AssetMovement;
use App\Models\AssetTransactionItem;
use App\Models\Assets;
use App\Models\InventoryAsset;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Notifications\Notification;
use App\Services\FormulaValueBinderService;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Illuminate\Support\Facades\Storage;

class CreateAssetTransaction extends CreateRecord
{
    protected static string $resource = AssetTransactionResource::class;
    
    protected static ?string $title = 'Transaksi Asset';

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (($data['input_mode'] ?? null) === 'IMPORT') {
            $path = storage_path('app/public/' . ($data['file_asset']));

            if (!file_exists($path)) {
                Notification::make()
                    ->title('File tidak ditemukan!')
                    ->danger()
                    ->send();

               throw ValidationException::withMessages([
                        'list_pole' => 'File Item tidak ditemukan.',
                    ]);
            }

            $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];
            // $sheet = $spreadsheet->getActiveSheet()->toArray();

            if (count($sheet) < 1) {
                Notification::make()
                    ->title('File Excel kosong!')
                    ->danger()
                    ->send();
                 throw ValidationException::withMessages([
                        'list_pole' => 'File Item tidak ditemukan.',
                    ]);
            }

            // Validasi header
            $header = $sheet[0];
            $expectedHeader = ['nama item', 'merk item', 'tipe item','serialNumber', 'kondisi',  'catatan'];
            if ($header !== $expectedHeader) {
                Notification::make()
                    ->title('Urutan kolom Excel salah!')
                    ->body('Pastikan urutan kolom sesuai: ' . implode(', ', $expectedHeader))
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }

            // Validasi duplikat serialNumber
            $serialNumbersInFile = [];
            foreach (array_slice($sheet, 1) as $row) {
                $serialNumber = $row[4] ?? null;
                if (!$serialNumber) continue;

                if (in_array($serialNumber, $serialNumbersInFile)) {
                    Notification::make()
                        ->title('Duplikat Serial Number di file!')
                        ->body("Serial Number $serialNumber muncul lebih dari sekali.")
                        ->danger()
                        ->send();
                    $this->halt();
                    return;
                }
                $serialNumbersInFile[] = $serialNumber;

                $existsInDb = Assets::where('serialNumber', $serialNumber)->exists();
                if ($existsInDb) {
                    Notification::make()
                        ->title('Serial Number sudah ada di database!')
                        ->body("Serial Number $serialNumber sudah digunakan.")
                        ->danger()
                        ->send();
                    $this->halt();
                    return;
                }
            }

            // Jika semua valid, set requested_items
            $items = [];
            foreach (array_slice($sheet, 1) as $row) {
                $assetId = $row[0] ?? null;
                $asset = Assets::find($assetId);
                if ($asset) {
                    $items[] = [
                        'asset_id' => $asset->id,
                        'item_code' => $asset->item_code,
                        'merk' => $asset->merk,
                        'type' => $asset->type,
                        'serialNumber' => $asset->serialNumber,
                        'description' => $asset->description,
                    ];
                }
            }

            $this->form->fill([
                'requested_items' => $items,
            ]);
        }    
        
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $assetTransaction = static::getModel()::create($data);

            if (!empty($data['requested_items'])) {


                
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
                            $inventory->outWarehouse -= $data['request_asset_qty'];
                            $inventory->save();
                        }
                    }
                 
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

                    }elseif ($assetTransaction->usage_type === 'RETURN WAREHOUSE') {

                        $asset = Assets::find($item['asset_id']);
                        if (!$asset) {
                            \Log::warning('Asset not found for RELEASE', ['item' => $item]);
                            continue;
                        }

                        $nextMoveId = (AssetMovement::max('id') ?? 0) + 1;

                        \Log::info('Usage type:', ['usage_type' => $data['usage_type'] ?? null]);
                       
                        // $movement = AssetMovement::create([
                        //     'movement_id'         => 'MOV' . str_pad($nextMoveId, 5, '0', STR_PAD_LEFT),
                        //     'asset_transaction_id'=> $assetTransaction->id,
                        //     'asset_id'            => $asset->id,
                        //     'movementType'        => 'IN',
                        //     'movementDate'        => now()->toDateString(),
                        //     'deployment_date'     => now()->toDateString(),
                        //     'PIC'                 => $data['PIC'],
                        //     'serialNumber'        => $item['serialNumber'] ?? null,
                        //     'placement_type'      => $data['usage_type'] ?? '',
                        //     'assigned_to'         => $data['assigned_id'] ?? null,
                        //     'recipient'           => $data['recipient_by'] ?? null,
                        //     'sender'              =>  null,
                        //     'location'            => $this->getDeploymentLocation($data),
                        //     'province_code'       => $data['province_code'] ?? null,
                        //     'regency_code'        => $data['regency_code'] ?? null,
                        //     'village_code'        => $data['village_code'] ?? null,
                        //     'recipient'           => $data['recipient_by'] ?? null,
                        //     'created_by'          => auth()->user()->name,
                        //     'status'              => 0,
                        // ]);

                        // AssetTransactionItem::create([
                        //     'asset_transaction_id' => $assetTransaction->id,
                        //     'asset_id'             => $asset->id,
                        //     'item_code'            => $asset->item_code,
                        //     'merk'                 => $asset->merk,
                        //     'type'                 => $asset->type,
                        //     'serial_number'        => $asset->serialNumber,
                        //     'description'          => $asset->description,
                        //     'movement_id'          => $movement->id,
                        // ]);

                        if($data['usage_type'] === "STOCK IN WAREHOUSE"){
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


                        $asset->status = 0; 
                        $asset->save();

                        

                    }
                     else {

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

                         if($data['usage_type'] === "STOCK IN WAREHOUSE"){
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
