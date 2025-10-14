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

class CategoryAssetResource extends Resource
{
    protected static ?string $model = CategoryAsset::class;

    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Category Assets';
    protected static ?string $pluralLabel = 'Category Assets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('category_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('category_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
            'index' => Pages\ListCategoryAssets::route('/'),
            'create' => Pages\CreateCategoryAsset::route('/create'),
            'edit' => Pages\EditCategoryAsset::route('/{record}/edit'),
        ];
    }
}
