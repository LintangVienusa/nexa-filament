<?php

namespace App\Filament\Resources;


use App\Filament\Resources\AssetTransactionResource\Pages;
use App\Filament\Resources\AssetTransactionResource\RelationManagers;
use App\Models\AssetTransaction;
use App\Models\Assets;
use App\Models\Employee;
use App\Models\MappingRegion;
use App\Models\CategoryAsset;
use App\Models\InventoryAsset;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class AssetTransactionResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = AssetTransaction::class;

    protected static ?string $title = 'Transaksi Asset';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Asset Transaction';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 0;

    public static function canEdit($record): bool
    {
        return false; // atau logika sesuai role
    }

    public static function form(Form $form): Form
    {
        return $form
                ->schema([
                    Hidden::make('created_by')
                        ->default(fn () => Auth::user()->email)
                        ->dehydrated(true),
                    Section::make('Informasi Transaksi')
                        ->schema([
                            Hidden::make('transaction_code')
                                ->label('Transaksi ID')
                                ->disabled()
                                ->reactive()
                                ->default(function ($record) {
                                    if ($record?->transaction_code) {
                                        return $record->transaction_code;
                                    } else {
                                        $last = Assettransaction::latest('id')->first();
                                        $nextId = $last ? $last->id + 1 : 1;
                                        return 'ASR' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                                    }
                                })
                                ->dehydrated(true)
                                ->required(),
                            Select::make('transaction_type')
                                    ->label('Transaksi Tipe')
                                    ->options([
                                        'RELEASE' => 'Release',
                                        'RECEIVE' => 'Receive',
                                    ])
                                    ->required()
                                    ->reactive(),

                            Select::make('PIC')
                                ->label(function (callable $get) {
                                        return $get('transaction_type') === 'RECEIVE'
                                            ? 'PIC Receive'
                                            : 'PIC Request';
                                    })
                                ->options(
                                    Employee::get()->mapWithKeys(fn ($emp) => [
                                        $emp->email => $emp->full_name
                                    ])
                                )
                                ->reactive()
                                ->searchable()
                                ->required()
                                ->default(fn ($record) =>
                                    $record?->email ?? auth()->user()->employee?->email
                                )
                                ->disabled(fn ($state, $component, $record) =>
                                    $record !== null || auth()->user()->isStaff()
                                )
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $employee = Employee::find($state);
                                        $set('email', $employee?->email);
                                    }
                                })
                                ->dehydrated(true),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Section::make('Kategori & BA')
                        ->schema([
                            Select::make('category_id')
                                ->label('Kategori')
                                ->options(CategoryAsset::query()->pluck('category_name', 'id'))
                                ->searchable()
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                    if ($state) {
                                        $alias = $get('transaction_type') === 'RECEIVE' ? 'RC' : 'RL';
                                        $totalQty = InventoryAsset::where('categoryasset_id', $state)
                                            ->sum('inWarehouse');
                                        $set('asset_qty_now', $totalQty);

                                        $category = CategoryAsset::find($state);
                                        if ($category) {
                                            $categoryCode = $category->category_code;
                                            $month = now()->format('m');
                                            $year = now()->format('Y');

                                            $count = Assettransaction::whereMonth('created_at', $month)
                                                ->whereYear('created_at', $year)
                                                ->count() + 1;
                                                $random = rand(1000, 9999);

                                            $number = str_pad($count, 3, '0', STR_PAD_LEFT);
                                            $baNumber = "DPN/BA/{$categoryCode}/{$alias}/{$number}/{$month}/{$year}/{$random}";

                                            $set('ba_number', $baNumber);
                                        }

                                        if ($category) {
                                            $nextId = (Assets::max('id') ?? 0) + 1;
                                            $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                                            $set('item_code', $category->category_code . $formattedId);
                                            // $set('item_code', null);s

                                        } else {
                                            $set('item_code', null);
                                        }
                                    } else {
                                        $set('asset_qty_now', 0);
                                        $set('item_code', null);
                                    }
                                }),

                            TextInput::make('asset_qty_now')
                                ->label('Jumlah Stock')
                                ->required()
                                ->disabled()
                                ->numeric()
                                ->dehydrated(true)
                                ->default(0),

                            TextInput::make('request_asset_qty')
                                ->label(fn (callable $get) => $get('transaction_type') === 'RECEIVE' ? 'Jumlah Receive' : 'Jumlah Request')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->default(0)
                                ->validationMessages([
                                    'max' => 'Jumlah request tidak boleh melebihi jumlah stock yang tersedia.',
                                ])
                                ->rule(fn (callable $get) => $get('transaction_type') === 'RELEASE' ? 'max:' . (int) $get('asset_qty_now') : null)
                                ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                    $currentItems = $get('requested_items') ?? [];
                                    $needed = $state ?? 0;

                                    while (count($currentItems) < $needed) {
                                        $currentItems[] = [
                                            'asset_id' => null,
                                            'item_code' => '',
                                            'merk' => '',
                                            'type' => '',
                                            'description' => '',
                                            'status' => 0,
                                            'quantity' => 1,
                                        ];
                                    }

                                    if (count($currentItems) > $needed) {
                                        $currentItems = array_slice($currentItems, 0, $needed);
                                    }

                                    $set('requested_items', $currentItems);
                                    $set('request_asset_qty', $needed);
                                }),

                            TextInput::make('ba_number')
                                ->label('Nomor BA')
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(true) ,

                            Textarea::make('ba_description')
                                ->label('Deskripsi')
                                ->columnSpanFull(),
                        ])
                         ->columns(2),

                    Section::make('Usage & Assignment')
                        ->schema([
                            Select::make('usage_type')
                                ->label('Tipe Penggunaan')
                                ->options([
                                    'ASSIGNED_TO_EMPLOYEE' => 'Operational Kantor',
                                    'DEPLOYED_FIELD' => 'Operational Lapangan',
                                    // 'RETURN WAREHOUSE' => 'Pengembalian ke Gudang',
                                    // 'STOCK IN WAREHOUSE' => 'Masuk ke Gudang',
                                ])
                                ->reactive()->required()
                                ->default('ASSIGNED_TO_EMPLOYEE')->dehydrated(true)
                                ->afterStateUpdated(fn ($state, callable $set) => $set('usage_type', $state)),

                            Select::make('assigned_type')
                                ->label('Jenis Penerima')
                                ->options([
                                    'EMPLOYEE' => 'Karyawan',
                                    'CONTRACTOR' => 'Kontraktor',
                                    'DISTRIBUTOR' => 'Distributor',
                                ])
                                ->reactive()->required()
                                ->afterStateUpdated(fn($state,$set)=> $set('recipient_by',null)),

                            Select::make('recipient_by')
                                ->label('Diterima Oleh')
                                ->searchable()
                                ->options(function(callable $get) {
                                    $type = $get('assigned_type');
                                    if($type==='EMPLOYEE') return Employee::get()->mapWithKeys(fn ($emp) => [
                                            $emp->employee_id => $emp->full_name,
                                        ]);
                                    if($type==='CONTRACTOR' || $type==='DISTRIBUTOR') return Customer::pluck('customer_name','id');
                                    return [];
                                }),

                            Select::make('province_code')
                                ->label('Kode Provinsi')
                                ->searchable()
                                ->options(MappingRegion::pluck('province_name','province_code'))->required()
                                ->reactive(),

                            Select::make('regency_code')
                                ->label('Kode Kabupaten')
                                ->searchable()
                                ->options(function(callable $get){
                                    $province = $get('province_code');
                                    if(!$province) return [];
                                    return MappingRegion::where('province_code',$province)->pluck('regency_name','regency_code');
                                })
                                ->reactive()->required(),

                            Select::make('village_code')
                                ->label('Kode Desa')
                                ->searchable()
                                ->options(function(callable $get){
                                    $regency = $get('regency_code');
                                    if(!$regency) return [];
                                    return MappingRegion::where('regency_code',$regency)->pluck('village_name','village_code');
                                })->required(),
                        ])
                        ->visible(fn (callable $get) => $get('transaction_type') === 'RELEASE')
                        ->columns(2),

                    Section::make('Penerimaan Barang')
                        ->schema([
                            Select::make('usage_type')
                                ->label('Usage Tipe')
                                ->options([
                                    'RETURN WAREHOUSE' => 'Pengembalian ke Gudang',
                                    'STOCK IN WAREHOUSE' => 'Masuk ke Gudang',
                                ])
                                ->reactive()->dehydrated(true),

                            Select::make('recipient_by')
                                ->label('Penerima')
                                ->searchable()
                                ->options(function(callable $get)
                                    {return Employee::get()->mapWithKeys(fn ($emp) => [
                                            $emp->employee_id => $emp->full_name]);
                                        }),

                            Select::make('sender_by')
                                ->label('Pengirim')
                                ->searchable()
                                ->options(function(callable $get)
                                    {return Employee::get()->mapWithKeys(fn ($emp) => [
                                            $emp->employee_id => $emp->full_name])->toArray() + ['other' => 'Lainnya'];;
                                        })
                                ->reactive()
                                ->visible(fn (callable $get) => $get('usage_type') !== 'STOCK IN WAREHOUSE') // 👈 tampil jika BUKAN STOCK IN WAREHOUSE
                                ->required(fn (callable $get) => $get('usage_type') !== 'STOCK IN WAREHOUSE'),

                            TextInput::make('sender_custom')
                                ->label('Nama Pengirim (Lainnya)')
                                ->reactive()
                                ->visible(fn (callable $get) => $get('usage_type') === 'STOCK IN WAREHOUSE')
                                ->required(fn (callable $get) => $get('usage_type') === 'STOCK IN WAREHOUSE'),

                            Select::make('province_code')
                                ->label('Kode Provinsi')
                                ->searchable()
                                ->options(MappingRegion::pluck('province_name','province_code'))
                                ->reactive(),

                            Select::make('regency_code')
                                ->label('Kode Kabupaten')
                                ->searchable()
                                ->options(function(callable $get){
                                    $province = $get('province_code');
                                    if(!$province) return [];
                                    return MappingRegion::where('province_code',$province)->pluck('regency_name','regency_code');
                                })
                                ->reactive(),

                            Select::make('village_code')
                                ->label('Kode Desa')
                                ->searchable()
                                ->options(function(callable $get){
                                    $regency = $get('regency_code');
                                    if(!$regency) return [];
                                    return MappingRegion::where('regency_code',$regency)->pluck('village_name','village_code');
                                }),

                            // Select::make('sender')
                            //     ->label('Pengirim')
                            //     ->options(function(callable $get)
                            //         {return Customer::pluck('customer_name','id');
                            //         return [];
                            //             }),
                        ])
                        ->columns(2)
                        ->visible(fn (callable $get) => $get('transaction_type') === 'RECEIVE'),

                    Section::make('Detail item')
                        ->schema([
                            Repeater::make('requested_items')
                                ->label('Detail item')
                                ->schema([
                                    Select::make('asset_id')
                                        ->label('Serial Number')
                                        ->options(function (callable $get) {
                                            $categoryId = $get('../../category_id');
                                            if (!$categoryId) return [];
                                            return Assets::where('category_id', $categoryId)
                                                ->where('status', 0)
                                                ->pluck('serialNumber', 'id');
                                        })
                                        ->reactive()
                                        ->searchable()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {

                                            $items = collect($get('../../requested_items'))->pluck('asset_id')->filter();

                                                if ($items->duplicates()->isNotEmpty()) {
                                                    $set('asset_id', null);
                                                    Notification::make()
                                                        ->title('Serial Number sudah digunakan di item lain!')
                                                        ->danger()
                                                        ->duration(3000)
                                                        ->send();

                                                    return;
                                                }
                                            $asset = Assets::find($state);
                                            if ($asset) {
                                                $set('item_code', $asset->item_code);
                                                $set('merk', $asset->merk);
                                                $set('type', $asset->type);
                                                $set('serialNumber', $asset->serialNumber);
                                                $set('description', $asset->description);
                                                $set('status', 0);
                                            }
                                        })
                                        ->required(),

                                    TextInput::make('item_code')
                                                ->label('Code Item')
                                                ->disabled()
                                                ->dehydrated(false),
                                    TextInput::make('merk')
                                                ->label('Merk')
                                                ->disabled()
                                                ->dehydrated(false) ,
                                    TextInput::make('type')
                                                ->label('Tipe Item')
                                                ->disabled()
                                                ->dehydrated(false),
                                    TextInput::make('serialNumber')
                                                ->label('Serial Number')
                                                ->disabled()
                                                ->dehydrated(false),
                                    Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->disabled()
                                            ->dehydrated(false),
                                ])
                                ->columns(2)
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->visible(fn (callable $get) => $get('transaction_type') === 'RELEASE' )
                                ->maxItems(fn (callable $get) => $get('../../request_asset_qty') ?? null)
                                ->required(),
                        
                                Repeater::make('requested_items')
                                ->label('Detail item')
                                ->schema([
                                    Select::make('asset_id')
                                        ->label('Serial Number')
                                        ->options(function (callable $get) {
                                            $categoryId = $get('../../category_id');
                                            if (!$categoryId) return [];
                                            return Assets::where('category_id', $categoryId)
                                                ->where('status', 1)
                                                ->pluck('serialNumber', 'id');
                                        })
                                        ->reactive()
                                        ->searchable()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {

                                            $items = collect($get('../../requested_items'))->pluck('asset_id')->filter();

                                                if ($items->duplicates()->isNotEmpty()) {
                                                    $set('asset_id', null);
                                                    Notification::make()
                                                        ->title('Serial Number sudah digunakan di item lain!')
                                                        ->danger()
                                                        ->duration(3000)
                                                        ->send();

                                                    return;
                                                }
                                            $asset = Assets::find($state);
                                            if ($asset) {
                                                $set('item_code', $asset->item_code);
                                                $set('merk', $asset->merk);
                                                $set('type', $asset->type);
                                                $set('serialNumber', $asset->serialNumber);
                                                $set('description', $asset->description);
                                                $set('status', 0);
                                            }
                                        })
                                        ->required(),

                                    TextInput::make('item_code')
                                                ->label('Code Item')
                                                ->disabled()
                                                ->dehydrated(false),
                                    TextInput::make('merk')
                                                ->label('Merk')
                                                ->disabled()
                                                ->dehydrated(false) ,
                                    TextInput::make('type')
                                                ->label('Tipe Item')
                                                ->disabled()
                                                ->dehydrated(false),
                                    TextInput::make('serialNumber')
                                                ->label('Serial Number')
                                                ->disabled()
                                                ->dehydrated(false),
                                    Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->disabled()
                                            ->dehydrated(false),
                                ])
                                ->columns(2)
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->visible(fn (callable $get) => $get('usage_type') === 'RETURN WAREHOUSE')
                                ->maxItems(fn (callable $get) => $get('../../request_asset_qty') ?? null)
                                ->required(),
                        
                        

                        Repeater::make('requested_items')
                                ->label('Detail Item')
                                ->schema([
                                    Section::make('Asset Information')
                                        ->schema([

                                            TextInput::make('name')
                                                ->label('Nama Item')
                                                ->required()->dehydrated(true),

                                            TextInput::make('merk')
                                                ->label('Merk')->dehydrated(true),

                                            TextInput::make('type')
                                                ->label('Tipe Item')->dehydrated(true),

                                            TextInput::make('serialNumber')
                                                ->label('Serial Number')
                                                ->required()
                                                ->reactive()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                    $allItems = $get('../../requested_items');
                                                    $serialNumbers = collect($allItems)
                                                        ->pluck('serialNumber')
                                                        ->filter()
                                                        ->toArray();
                                                        if (blank($state)) return;

                                                            $allItems = $get('../../requested_items');
                                                            $serialNumbers = collect($allItems)
                                                                ->pluck('serialNumber')
                                                                ->filter()
                                                                ->toArray();

                                                            $duplicates = array_count_values($serialNumbers);
                                                            $hasDuplicate = ($duplicates[$state] ?? 0) > 1;

                                                            if ($hasDuplicate) {
                                                                $set('serialNumber', null);

                                                                Notification::make()
                                                                    ->title('Serial number sudah digunakan di item lain!')
                                                                    ->danger()
                                                                    ->send();
                                                            }

                                                            if (Assets::where('serialNumber', $state)->exists()) {
                                                                    $set('serialNumber', null);
                                                                    Notification::make()
                                                                        ->title("Serial number '{$state}' sudah terdaftar di database Assets!")
                                                                        ->danger()
                                                                        ->send();
                                                                }
                                                })
                                                ->rule(function (callable $get) {
                                                    return function (string $attribute, $value, $fail) use ($get) {

                                                        $parent = $get('../../transaction_type');
                                                        if ($parent === 'RECEIVE') {
                                                            $exists = \App\Models\Assets::where('serialNumber', $value)->exists();
                                                            if ($exists) {
                                                                $fail("Serial number '{$value}' sudah terdaftar di database Assets.");
                                                            }
                                                        }
                                                    };
                                                })
                                                ->validationMessages([
                                                    'required' => 'Serial number wajib diisi.',
                                                ]),


                                            Select::make('asset_condition')
                                                ->label('Kondisi Aset')
                                                ->options([
                                                    'GOOD' => 'Bagus',
                                                    'DAMAGED' => 'Rusak',
                                                    'REPAIR' => 'Perlu Perbaikan',
                                                ])
                                                ->reactive()
                                                ->required()->dehydrated(true),

                                            TextArea::make('notes')
                                                ->label('Catatan')->dehydrated(true),
                                        ])
                                    ->columns(2)
                            ])
                                ->disableItemCreation()
                                ->disableItemDeletion()

                                ->maxItems(fn (callable $get) => $get('../../request_asset_qty') ?? null)
                                ->visible(fn (callable $get) => $get('transaction_type') === 'RECEIVE' && $get('usage_type') != 'RETURN WAREHOUSE'),
                        ]),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('PIC')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asset_qty_now')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_asset_qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ba_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetTransactions::route('/'),
            'create' => Pages\CreateAssetTransaction::route('/create'),
            'edit' => Pages\EditAssetTransaction::route('/{record}/edit'),
        ];
    }
}
