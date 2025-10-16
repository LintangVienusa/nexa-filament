<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetReleaseResource\Pages;
use App\Filament\Resources\AssetReleaseResource\RelationManagers;
use App\Models\AssetRelease;
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
                TextInput::make('asset_release_id')
                    ->label('Asset Release ID')
                    ->disabled() 
                    ->reactive()
                    ->default(function ($record) {
                        if ($record?->asset_release_id) {
                            return $record->asset_release_id;
                        } else {
                            $last = \App\Models\AssetRelease::latest('id')->first();
                            $nextId = $last ? $last->id + 1 : 1;
                            return 'ASR' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                        }
                    })
                    ->dehydrated(true)
                    ->required(),
                Forms\Components\Select::make('PIC')
                    ->label('PIC Request')
                    ->options(\App\Models\Employee::all()->pluck('full_name', 'employee_id'))
                    ->searchable()
                    ->required()
                    ->default(fn ($record) => 
                        $record?->employee_id 
                        ?? auth()->user()->employee?->employee_id
                    )
                    ->disabled(fn ($state, $component, $record) => 
                        $record !== null || auth()->user()->isStaff()
                    ) 
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $employee = \App\Models\Employee::find($state);
                            $set('employee_id', $employee?->employee_id);
                        }
                    })->dehydrated(true),
                
                Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(\App\Models\CategoryAsset::query()->pluck('category_name', 'id')) // tampil nama kategori
                    ->searchable()
                    ->reactive() // supaya bisa trigger perubahan field lain jika perlu
                    ->required()
                    ->afterStateUpdated(function (callable $set, $state) {
                            if ($state) {
                                // Ambil total inWarehouse dari semua inventory dengan category_id tersebut
                                $totalQty = \App\Models\InventoryAsset::where('categoryasset_id', $state)
                                    ->sum('inWarehouse');

                                $set('asset_qty_now', $totalQty);
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
                        // Set jumlah repeater sesuai request qty
                        $set('requested_items', array_fill(0, $state ?? 0, ['detail' => '']));
                    }),
                Repeater::make('requested_items')
                    ->label('Detail Request Items')
                    ->schema([
                       Select::make('asset_id')
                            ->label('Asset Name')
                            ->options(function (callable $get) {
                                $categoryId = $get('../../category_id'); 
                                if (!$categoryId) {
                                    return [];
                                }

                                return \App\Models\Assets::where('category_id', $categoryId)
                                    ->where('status', 0)
                                    ->get()
                                    ->mapWithKeys(function ($asset) {
                                        return [
                                            $asset->id => "{$asset->name} | {$asset->merk} | {$asset->type} | {$asset->serialNumber}",
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                
                                        dd(\App\Models\Assets::find($state));
                                    if ($state) {
                                        $asset = \App\Models\Assets::find($state);
                                        if ($asset) {
                                            $set('item_code', $asset->item_code);
                                            $set('merk', $asset->merk);
                                            $set('type', $asset->type);
                                            $set('serialNumber', $asset->serialNumber);
                                        }
                                    } else {
                                        $set('item_code', "OFF");
                                        $set('merk', null);
                                        $set('type', null);
                                        $set('serialNumber', null);
                                        return;
                                    }
                                }),
                        TextInput::make('item_code')
                            ->label('Item Code')
                            ->disabled()
                            ->reactive()
                            ->dehydrated(false)
                    ])
                    ->columns(2)
                    ->disableItemCreation() // supaya user tidak menambah sendiri, dikontrol oleh request_asset_qty
                    ->disableItemDeletion(),
                TextInput::make('ba_number')
                    ->maxLength(255),
                Textarea::make('ba_description')
                    ->columnSpanFull(),
                TextInput::make('file_path')
                    ->maxLength(255),
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('created_by')
                    ->maxLength(255),
                TextInput::make('approved_by')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('approved_at'),
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
