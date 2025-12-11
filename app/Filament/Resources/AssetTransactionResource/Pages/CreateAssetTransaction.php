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

        $category = \App\Models\CategoryAsset::find($data['category_id']);

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

            if($category->info_sn ==='yes'){
                $serialNumbersInFile = [];
                if($data['transaction_type'] === 'RECEIVE'){
                    foreach (array_slice($sheet, 1) as $row) {
                        $serialNumber = $row[3] ?? null;
                        if(empty($serialNumber)){
                            Notification::make()
                                ->title("Serial Number belum terisi")
                                ->body("Serial Number untuk $category->category_name tidak boleh kosong.")
                                ->danger()
                                ->send();
                            $this->halt();
                            return;
                        }
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
                        if($data['usage_type'] === 'STOCK IN WAREHOUSE'){
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
                    }
                }else{
                    foreach (array_slice($sheet, 1) as $row) {
                        $serialNumber = $row[3] ?? null;
                        if(empty($serialNumber)){
                            Notification::make()
                                ->title("Serial Number belum terisi")
                                ->body("Serial Number untuk $category->category_name tidak boleh kosong.")
                                ->danger()
                                ->send();
                            $this->halt();
                            return;
                        }
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

                        $existsInDb = Assets::where('serialNumber', $serialNumber)->where('status', 1)->exists();
                        if ($existsInDb) {
                            Notification::make()
                                ->title('Serial Number sudah keluar WAREHOUSE')
                                ->body("Serial Number $serialNumber status sudah digunakan.")
                                ->danger()
                                ->send();
                            $this->halt();
                            return;
                        }

                        $existsInDb2 = Assets::where('serialNumber', $serialNumber)->exists();
                        if (!$existsInDb2) {
                            Notification::make()
                                ->title('Serial Number belum ada di database!')
                                ->body("Serial Number $serialNumber belum terdaftar.")
                                ->danger()
                                ->send();
                            $this->halt();
                            return;
                        }
                    }

                }
            }

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

            // $this->form->fill([
            //     'requested_items' => $items,
            // ]);
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
                            'created_by'          => auth()->user()->email,
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
                        $asset->updated_by = auth()->user()->email; 
                        $asset->updated_at = now(); 
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
                            'created_by'          => auth()->user()->email,
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
                        $asset->updated_by = auth()->user()->email; 
                        $asset->updated_at = now(); 
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
                            'created_by'          => auth()->user()->email,
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

                
                return $assetTransaction;
            }else{
                $category = \App\Models\CategoryAsset::find($data['category_id']);
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
                if ($assetTransaction->transaction_type === 'RELEASE') {
                     if($category->info_sn === 'no'){
                        $qty = $data['request_asset_qty'];
                        
                        $assets = Assets::where('status',0)
                                ->orderBy('id', 'asc')
                                ->limit($qty)
                                ->get();
                        foreach ($assets as $asset) {
                            $asset->status = 1;
                            
                            $asset->updated_by = auth()->user()->email; 
                            $asset->updated_at = now(); 
                             
                            $asset->save();

                            $nextMoveId = (AssetMovement::max('id') ?? 0) + 1;

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
                                'created_by'          => auth()->user()->email,
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

                     }else{
                            
                            // dd($assetTransaction);
                            if($assetTransaction->input_mode === 'IMPORT'){
                            
                                $file = $assetTransaction->file_asset;
                                $path = storage_path('app/public/' . $file);
                                if (!file_exists($path)) return;

                                $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];
                                $spreadsheet = IOFactory::load($path);
                                $sheetExcel = $spreadsheet->getActiveSheet();
                                $startNumber = 1; 
                                $counter = 0;
                                $validPoleList = [];

                                for ($i = 1; $i <= $sheetExcel->getHighestRow(); $i++) {
                                    
                                    
                                    $nextId = (Assets::max('id') ?? 0) + 1;
                                    $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                                    $itemCode = ($category?->category_code ?? 'CAT') . $formattedId;

                                    $cname    = $sheetExcel->getCell('A' . $i);
                                    $cmerk    = $sheetExcel->getCell('B' . $i);
                                    $ctype    = $sheetExcel->getCell('C' . $i);
                                    $csn      = $sheetExcel->getCell('D' . $i);
                                    $ckondisi = $sheetExcel->getCell('E' . $i);
                                    $cnotes   = $sheetExcel->getCell('F' . $i);

                                    $name    = $cname->getCalculatedValue(); 
                                    $merk    = $cmerk->getCalculatedValue();
                                    $type    = $ctype->getCalculatedValue();
                                    $sn      = $csn->getCalculatedValue();
                                    $kondisi = $ckondisi->getCalculatedValue();
                                    $notes   = $cnotes->getCalculatedValue();
                                    
                                    // if($category->info_sn === 'no'){
                                    //     $code = $category->category_code;
                                    //     $tahun = date('Y');
                                    //     $bulan = date('m');
                                    //     $tanggal = date('d');
                                    //     $sn = $code . $tahun . $bulan . $tanggal . rand(1000, 9999);
                                    // }
                                    $asset = Assets::where('serialNumber', $sn)->first();

                                    $kondisi_map = [
                                        'BAGUS' => 'GOOD',
                                        'RUSAK' => 'DAMAGED',
                                        'PERLU PERBAIKAN' => 'REPAIR',
                                    ];
                                    $kondisi_new = $kondisi_map[strtoupper($kondisi)] ?? 'GOOD';

                                    if (empty($name) || strtoupper(trim($name)) === 'NAMA ITEM' 
                                    && empty($merk) || strtoupper(trim($merk)) === 'MERK ITEM'
                                    && empty($type) || strtoupper(trim($type)) === 'TIPE ITEM' 
                                    && empty($sn) || strtoupper(trim($sn)) === 'SERIALNUMBER' 
                                    && empty($kondisi_new) || strtoupper(trim($kondisi_new)) === 'KONDISI' 
                                    && empty($notes) || strtoupper(trim($notes)) === 'CATATAN' ) continue;

                                    
                                    
                                    $asset->status = 1; 
                                    
                                    $asset->asset_condition = $kondisi_new; 
                                    $asset->updated_by = auth()->user()->email; 
                                    $asset->updated_at = now(); 
                                    $asset->save();

                                    

                                    $nextMoveId = (AssetMovement::max('id') ?? 0) + 1;
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
                                        'created_by'          => auth()->user()->email,
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
                }elseif($data['usage_type'] ==='RETURN WAREHOUSE'){
                    
                    if($assetTransaction->input_mode === 'IMPORT'){
                    
                        $file = $assetTransaction->file_asset;
                        $path = storage_path('app/public/' . $file);
                        if (!file_exists($path)) return;

                        $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];
                        $spreadsheet = IOFactory::load($path);
                        $sheetExcel = $spreadsheet->getActiveSheet();
                        $startNumber = 1; 
                        $counter = 0;
                        $validPoleList = [];

                        for ($i = 1; $i <= $sheetExcel->getHighestRow(); $i++) {
                            
                            $nextId = (Assets::max('id') ?? 0) + 1;
                            $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                            $itemCode = ($category?->category_code ?? 'CAT') . $formattedId;

                            $cname    = $sheetExcel->getCell('A' . $i);
                            $cmerk    = $sheetExcel->getCell('B' . $i);
                            $ctype    = $sheetExcel->getCell('C' . $i);
                            $csn      = $sheetExcel->getCell('D' . $i);
                            $ckondisi = $sheetExcel->getCell('E' . $i);
                            $cnotes   = $sheetExcel->getCell('F' . $i);

                            $name    = $cname->getCalculatedValue(); 
                            $merk    = $cmerk->getCalculatedValue();
                            $type    = $ctype->getCalculatedValue();
                            $sn      = $csn->getCalculatedValue();
                            $kondisi = $ckondisi->getCalculatedValue();
                            $notes   = $cnotes->getCalculatedValue();
                            
                            if($category->info_sn === 'no'){
                                $code = $category->category_code;
                                $tahun = date('Y');
                                $bulan = date('m');
                                $tanggal = date('d');
                                $sn = $code . $tahun . $bulan . $tanggal . rand(1000, 9999);
                            }

                            $kondisi_map = [
                                'BAGUS' => 'GOOD',
                                'RUSAK' => 'DAMAGED',
                                'PERLU PERBAIKAN' => 'REPAIR',
                            ];
                            $kondisi_new = $kondisi_map[strtoupper($kondisi)] ?? 'GOOD';

                            if (empty($name) || strtoupper(trim($name)) === 'NAMA ITEM' 
                            && empty($merk) || strtoupper(trim($merk)) === 'MERK ITEM'
                            && empty($type) || strtoupper(trim($type)) === 'TIPE ITEM' 
                            && empty($sn) || strtoupper(trim($sn)) === 'SERIALNUMBER' 
                            && empty($kondisi_new) || strtoupper(trim($kondisi_new)) === 'KONDISI' 
                            && empty($notes) || strtoupper(trim($notes)) === 'CATATAN' ) continue;

                            $asset = Assets::where('serialNumber', $sn)->first();
                            $asset->status = 0; 
                            $asset->asset_condition = $kondisi_new; 
                            $asset->updated_by = auth()->user()->email; 
                            $asset->updated_at = now(); 
                            $asset->save();

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
                                'created_by'          => auth()->user()->email,
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

                }else{
                    // dd($assetTransaction);
                    if($assetTransaction->input_mode === 'IMPORT'){
                    
                        $file = $assetTransaction->file_asset;
                        $path = storage_path('app/public/' . $file);
                        if (!file_exists($path)) return;

                        $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];
                        $spreadsheet = IOFactory::load($path);
                        $sheetExcel = $spreadsheet->getActiveSheet();
                        $startNumber = 1; 
                        $counter = 0;
                        $validPoleList = [];

                        for ($i = 1; $i <= $sheetExcel->getHighestRow(); $i++) {
                            
                            $nextId = (Assets::max('id') ?? 0) + 1;
                            $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                            $itemCode = ($category?->category_code ?? 'CAT') . $formattedId;

                            $cname    = $sheetExcel->getCell('A' . $i);
                            $cmerk    = $sheetExcel->getCell('B' . $i);
                            $ctype    = $sheetExcel->getCell('C' . $i);
                            $csn      = $sheetExcel->getCell('D' . $i);
                            $ckondisi = $sheetExcel->getCell('E' . $i);
                            $cnotes   = $sheetExcel->getCell('F' . $i);

                            $name    = $cname->getCalculatedValue(); 
                            $merk    = $cmerk->getCalculatedValue();
                            $type    = $ctype->getCalculatedValue();
                            $sn      = $csn->getCalculatedValue();
                            $kondisi = $ckondisi->getCalculatedValue();
                            $notes   = $cnotes->getCalculatedValue();
                            
                            if($category->info_sn === 'no'){
                                $code = $category->category_code;
                                $tahun = date('Y');
                                $bulan = date('m');
                                $tanggal = date('d');
                                $sn = $code . $tahun . $bulan . $tanggal . rand(1000, 9999);
                            }

                            $kondisi_map = [
                                'BAGUS' => 'GOOD',
                                'RUSAK' => 'DAMAGED',
                                'PERLU PERBAIKAN' => 'REPAIR',
                            ];
                            $kondisi_new = $kondisi_map[strtoupper($kondisi)] ?? 'GOOD';

                            if (empty($name) || strtoupper(trim($name)) === 'NAMA ITEM' 
                            && empty($merk) || strtoupper(trim($merk)) === 'MERK ITEM'
                            && empty($type) || strtoupper(trim($type)) === 'TIPE ITEM' 
                            && empty($sn) || strtoupper(trim($sn)) === 'SERIALNUMBER' 
                            && empty($kondisi_new) || strtoupper(trim($kondisi_new)) === 'KONDISI' 
                            && empty($notes) || strtoupper(trim($notes)) === 'CATATAN' ) continue;

                            
                            $asset = Assets::create([
                                'item_code'       => $itemCode,
                                'name'            => $name ?? 'Unknown',
                                'type'            => $type ?? null,
                                'merk'            => $merk ?? null,
                                'serialNumber'    => $sn ?? null,
                                'category_id'     => $data['category_id'],
                                'description'     => $notes ?? null,
                                'asset_condition' => $kondisi_new ?? 'GOOD',
                                'notes'           => $notes ?? null,
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
                                'created_by'          => auth()->user()->email,
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
            }

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
