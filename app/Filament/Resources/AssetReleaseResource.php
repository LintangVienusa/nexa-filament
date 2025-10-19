<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetReleaseResource\Pages;
use App\Filament\Resources\AssetReleaseResource\RelationManagers;
use App\Models\AssetRelease;
use App\Models\Assets;
use App\Models\Employee;
use App\Models\CategoryAsset;
use App\Models\InventoryAsset;
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


class AssetReleaseResource extends Resource
{
    protected static ?string $model = AssetRelease::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Asset Release';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
                ->schema([

                    Section::make('Release Information')
                        ->schema([
                            TextInput::make('asset_release_id')
                                ->label('Asset Release ID')
                                ->disabled()
                                ->reactive()
                                ->default(function ($record) {
                                    if ($record?->asset_release_id) {
                                        return $record->asset_release_id;
                                    } else {
                                        $last = AssetRelease::latest('id')->first();
                                        $nextId = $last ? $last->id + 1 : 1;
                                        return 'ASR' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                                    }
                                })
                                ->dehydrated(true)
                                ->required(),

                            Select::make('PIC')
                                ->label('PIC Request')
                                ->options(Employee::all()->pluck('full_name', 'employee_id'))
                                ->searchable()
                                ->required()
                                ->default(fn ($record) => 
                                    $record?->employee_id ?? auth()->user()->employee?->employee_id
                                )
                                ->disabled(fn ($state, $component, $record) => 
                                    $record !== null || auth()->user()->isStaff()
                                )
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $employee = Employee::find($state);
                                        $set('employee_id', $employee?->employee_id);
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

                                            $count = AssetRelease::whereMonth('created_at', $month)
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
                                ->default(0),

                            TextInput::make('request_asset_qty')
                                ->label('Jumlah Request')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->default(0)
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
                                ->disabled(),

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
                                    'OFFICE' => 'Operational Kantor',
                                    'DEPLOYED_FIELD' => 'Operational Lapangan',
                                ])
                                ->reactive()
                                ->default('OFFICE'),

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
                                    if($type==='employee') return Employee::pluck('full_name','employee_id');
                                    if($type==='contractor') return Contractor::pluck('name','id');
                                    return [];
                                }),

                            Select::make('province_code')
                                ->label('Kode Provinsi')
                                ->options(Province::pluck('name','code'))
                                ->reactive()
                                ->visible(fn(callable $get)=> $get('usage_type')==='DEPLOYED_FIELD'),

                            Select::make('regency_code')
                                ->label('Kode Kabupaten')
                                ->options(function(callable $get){
                                    $province = $get('province_code');
                                    if(!$province) return [];
                                    return Regency::where('province_code',$province)->pluck('name','code');
                                })
                                ->reactive()
                                ->visible(fn(callable $get)=> $get('usage_type')==='DEPLOYED_FIELD'),

                            Select::make('village_code')
                                ->label('Kode Desa')
                                ->options(function(callable $get){
                                    $regency = $get('regency_code');
                                    if(!$regency) return [];
                                    return Village::where('regency_code',$regency)->pluck('name','code');
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

                                    TextInput::make('item_code')->label('Asset Code')->disabled(),
                                    TextInput::make('merk')->label('Merk')->disabled(),
                                    TextInput::make('type')->label('Asset Type')->disabled(),
                                    TextInput::make('serialNumber')->label('Serial Number')->disabled(),
                                    Textarea::make('description')->label('Keterangan')->disabled(),
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
                Tables\Columns\TextColumn::make('asset_release_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('asset_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('movement_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('PIC')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asset_qty_now')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_asset_qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ba_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_path')
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
            'index' => Pages\ListAssetReleases::route('/'),
            'create' => Pages\CreateAssetRelease::route('/create'),
            'edit' => Pages\EditAssetRelease::route('/{record}/edit'),
        ];
    }
}
