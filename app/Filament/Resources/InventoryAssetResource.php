<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryAssetResource\Pages;
use App\Filament\Resources\InventoryAssetResource\RelationManagers;
use App\Models\InventoryAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Summarizers\Sum;

class InventoryAssetResource extends Resource
{
    protected static ?string $model = InventoryAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Inventory Asset Summary';

    public static function canCreate(): bool
    {
        return false; 
    }

    public static function form(Form $form): Form
    {
        return $form
             ->schema([
                Forms\Components\Section::make('Asset Info')
                    ->schema([
                        Forms\Components\Select::make('categoryAsset_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('total')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Warehouse')
                    ->schema([
                        Forms\Components\TextInput::make('inWarehouse')
                            ->label('In Warehouse')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('outWarehouse')
                            ->label('Out Warehouse')
                            ->numeric()
                            ->nullable(),
                    ])
                    ->columns(2),

                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.category_id')
                    ->label('Category ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.category_name')
                    ->label('Category Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ?? '0'),
                Tables\Columns\TextColumn::make('inWarehouse')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? '0')
                    ->extraAttributes(['class' => 'font-bold text-right'])
                    ->summarize([
                            Sum::make()
                                ->label('')
                                ->formatStateUsing(fn ($state) => number_format($state ?? 0))
                                ->using(function ($query) {
                                    return $query->sum('inWarehouse');
                                })
                                ->extraAttributes(['class' => 'font-bold text-right'])
                        ]),
                Tables\Columns\TextColumn::make('outWarehouse')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? '0')
                    ->extraAttributes(['class' => 'font-bold text-right'])
                    ->summarize([
                            Sum::make()
                            ->label('')
                            ->formatStateUsing(fn ($state) => number_format($state ?? 0))
                            ->using(function ($query) {
                                return $query->sum('outWarehouse');
                            })
                            ->extraAttributes(['class' => 'font-bold text-right']) 
                        ]),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? '0')
                    ->extraAttributes(['class' => 'font-bold text-right'])
                    ->summarize([
                            Sum::make()
                            ->label('')
                            ->extraAttributes(['class' => 'font-bold text-right'])
                        ]),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),s
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
            'index' => Pages\ListInventoryAssets::route('/'),
            // 'create' => Pages\CreateInventoryAsset::route('/create'),
            // 'edit' => Pages\EditInventoryAsset::route('/{record}/edit'),
        ];
    }
}
