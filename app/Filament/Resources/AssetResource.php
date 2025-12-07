<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Assets;
use App\Models\CategoryAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class AssetResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = Assets::class;

     protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Assets';
    protected static ?int $navigationSort = 0;

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false; // atau logika sesuai role
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('General Info')
                    ->schema([
                        
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(CategoryAsset::query()->pluck('category_name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $category = CategoryAsset::find($state);
                                        if ($category) {
                                            $nextId = (Assets::max('id') ?? 0) + 1;
                                            $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                                            $set('item_code', $category->category_code . $formattedId);
                                
                                        } else {
                                            $set('item_code', null);
                                        }
                                    } else {
                                        $set('item_code', null);
                                    }
                                })
                            ->required(),
                        Forms\Components\TextInput::make('item_code')
                            ->label('Item Code')
                            ->readOnly() 
                            ->dehydrated(true) 
                            ->helperText('Akan diisi otomatis setelah disimpan'),

                        Forms\Components\TextInput::make('name')
                            ->label('Asset Name')
                            ->required(),

                        
                        Forms\Components\TextInput::make('merk')
                            ->label('Merk')
                            ->nullable(),

                        Forms\Components\TextInput::make('type')
                            ->label('Type')
                            ->nullable(),

                        Forms\Components\TextInput::make('serialNumber')
                            ->label('Serial Number')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('asset_condition')
                                ->label('Kondisi Aset')
                                ->options([
                                    'GOOD' => 'Bagus',
                                    'DAMAGED' => 'Rusak',
                                    'REPAIR' => 'Perlu Perbaikan',
                                ])
                                ->reactive()
                                ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Keterangan')->rows(3)
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Category & Status')
                    ->schema([
                        

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                0 => 'IN WAREHOUSE',
                                1 => 'OUT DEPLOYED',
                                2 => 'LOST',
                                4 => 'RETURNED',
                            ])
                            ->default(0),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')->rows(3)
                            ->nullable(),
                        

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->user()->email),
                    ])
                    ->columns(2),

                
            ])
            ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Type'),
                Tables\Columns\TextColumn::make('serialNumber')->label('Serial'),
                Tables\Columns\TextColumn::make('category.category_name')->label('Category')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => 'IN WAREHOUSE',
                        1 => 'OUT DEPLOYED',
                        2 => 'LOST',
                        4 => 'RETURNED',
                        default => 'Unknown',
                    }),
                Tables\Columns\TextColumn::make('created_by'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Created At'),
                Tables\Columns\TextColumn::make('updated_by')->label('Updated by'),
                Tables\Columns\TextColumn::make('updated_by')->dateTime()->label('Updated At'),
            
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
