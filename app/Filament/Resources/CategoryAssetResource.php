<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryAssetResource\Pages;
use App\Filament\Resources\CategoryAssetResource\RelationManagers;
use App\Models\CategoryAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class CategoryAssetResource extends Resource
{
     use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = CategoryAsset::class;

    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationLabel = 'Category Assets';
    protected static ?string $pluralLabel = 'Category Assets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kategori')
                    ->description('Data dasar untuk kategori asset')
                    ->columns(2) // jumlah kolom di section
                    ->schema([
                        TextInput::make('category_id')
                            ->label('Category ID')
                            ->disabled() // agar user tidak bisa ubah
                            ->default(function () {
                                $last = CategoryAsset::latest('category_id')->first();
                                if ($last) {
                                    $number = (int) substr($last->category_id, 2) + 1;
                                } else {
                                    $number = 1;
                                }
                                return 'CA' . str_pad($number, 4, '0', STR_PAD_LEFT);
                            }),
                        TextInput::make('category_code')
                            ->label('Category Code')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('category_name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCategoryAssets::route('/'),
            'create' => Pages\CreateCategoryAsset::route('/create'),
            'edit' => Pages\EditCategoryAsset::route('/{record}/edit'),
        ];
    }
}
