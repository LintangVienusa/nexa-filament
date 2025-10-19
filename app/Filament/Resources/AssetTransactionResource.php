<?php

namespace App\Filament\Resources;


use App\Filament\Resources\AssettransactionResource\Pages;
use App\Filament\Resources\AssettransactionResource\RelationManagers;
use App\Models\Assettransaction;
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

class AssetTransactionResource extends Resource
{
    protected static ?string $model = AssetTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Asset Transaction';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
                ->schema([
                    Hidden::make('created_by')
                        ->default(fn () => Auth::user()->email) 
                        ->dehydrated(true),
                    Section::make('transaction Information')
                        ->schema([
                            TextInput::make('transaction_code')
                                ->label('Asset transaction ID')
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
                                    ->label('Transaction Type')
                                    ->options([
                                        'RELEASE' => 'Release',
                                        'RECEIVE' => 'Receive',
                                    ])
                                    ->required(),

                            Select::make('PIC')
                                ->label('PIC Request')
                                ->options(
                                    Employee::get()->mapWithKeys(fn ($emp) => [
                                        $emp->email => $emp->full_name
                                    ])
                                )
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

                    Section::make('Category & BA')
                        ->schema([
                            Select::make('category_id')
                                ->label('Category')
                                ->options(CategoryAsset::query()->pluck('category_name', 'id'))
                                ->searchable()
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
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

                                            $number = str_pad($count, 3, '0', STR_PAD_LEFT);
                                            $baNumber = "BA/{$categoryCode}/{$number}/{$month}/{$year}";

                                            $set('ba_number', $baNumber);
                                        }
                                    } else {
                                        $set('asset_qty_now', 0);
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
                                ->label('Jumlah Request')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->default(0)
                                ->validationMessages([
                                    'max' => 'Jumlah request tidak boleh melebihi jumlah stock yang tersedia.',
                                ])
                                ->rule(function (callable $get) {
                                    $max = (int) $get('asset_qty_now');
                                    return 'max:' . $max;
                                })
                                ->afterStateUpdated(function (callable $set, $state) {
                                    $items = [];
                                    for ($i = 0; $i < ($state ?? 0); $i++) {
                                        $items[] = [
                                            'asset_id' => null,
                                            'item_code' => '',
                                            'merk' => '',
                                            'type' => '',
                                            'serialNumber' => '',
                                            'description' => '',
                                            'status' => 0,
                                            'quantity' => 1,
                                        ];
                                    }
                                    $set('requested_items', $items);
                                }),

                            TextInput::make('ba_number')
                                ->label('BA Number')
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(true) ,

                            Textarea::make('ba_description')
                                ->label('BA Description')
                                ->columnSpanFull(),
                        ])
                         ->columns(2),

                    Section::make('Usage & Assignment')
                        ->schema([
                            Select::make('usage_type')
                                ->label('Usage Type')
                                ->options([
                                    'ASSIGNED_TO_EMPLOYEE' => 'Operational Kantor',
                                    'DEPLOYED_FIELD' => 'Operational Lapangan',
                                    'WAREHOUSE' => 'Pengembalian ke Gudang',
                                ])
                                ->reactive()
                                ->default('ASSIGNED_TO_EMPLOYEE'),

                            // Assigned To
                            Select::make('assigned_type')
                                ->label('Assigned Type')
                                ->options([
                                    'employee' => 'Karyawan',
                                    'contractor' => 'Kontraktor',
                                ])
                                ->reactive()
                                ->afterStateUpdated(fn($state,$set)=> $set('assigned_id',null)),

                            Select::make('assigned_id')
                                ->label('Assigned To')
                                ->options(function(callable $get) {
                                    $type = $get('assigned_type');
                                    if($type==='employee') return Employee::get()->mapWithKeys(fn ($emp) => [
                                            $emp->employee_id => $emp->full_name,
                                        ]);
                                    if($type==='contractor') return Customer::pluck('customer_name','id');
                                    return [];
                                }),

                            Select::make('province_code')
                                ->label('Kode Provinsi')
                                ->options(MappingRegion::pluck('province_name','province_code'))
                                ->reactive()
                                ->visible(fn(callable $get)=> $get('usage_type')==='DEPLOYED_FIELD'),

                            Select::make('regency_code')
                                ->label('Kode Kabupaten')
                                ->options(function(callable $get){
                                    $province = $get('province_code');
                                    if(!$province) return [];
                                    return MappingRegion::where('province_code',$province)->pluck('regency_name','regency_code');
                                })
                                ->reactive()
                                ->visible(fn(callable $get)=> $get('usage_type')==='DEPLOYED_FIELD'),

                            Select::make('village_code')
                                ->label('Kode Desa')
                                ->options(function(callable $get){
                                    $regency = $get('regency_code');
                                    if(!$regency) return [];
                                    return MappingRegion::where('regency_code',$regency)->pluck('village_name','village_code');
                                })
                                ->visible(fn(callable $get)=> $get('usage_type')==='DEPLOYED_FIELD'),
                        ])
                        ->columns(2),

                    Section::make('Assets Detail')
                        ->schema([
                            Repeater::make('requested_items')
                                ->label('Assets Detail')
                                ->schema([
                                    Select::make('asset_id')
                                        ->label('Asset')
                                        ->options(function (callable $get) {
                                            $categoryId = $get('../../category_id');
                                            if (!$categoryId) return [];
                                            return Assets::where('category_id', $categoryId)
                                                ->where('status', 0)
                                                ->pluck('name', 'id');
                                        })
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
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
                                                ->label('Asset Code')
                                                ->disabled()
                                                ->dehydrated(true),
                                    TextInput::make('merk')
                                                ->label('Merk')
                                                ->disabled()
                                                ->dehydrated(true) ,
                                    TextInput::make('type')
                                                ->label('Asset Type')
                                                ->disabled()
                                                ->dehydrated(true),
                                    TextInput::make('serialNumber')
                                                ->label('Serial Number')
                                                ->disabled()
                                                ->dehydrated(true),
                                    Textarea::make('description')
                                            ->label('Keterangan')
                                            ->disabled()
                                            ->dehydrated(true),
                                ])
                                ->columns(2)
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->maxItems(fn (callable $get) => $get('../../request_asset_qty') ?? null)
                                ->required(),
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
