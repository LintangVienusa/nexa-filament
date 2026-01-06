<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MappingRegionResource\Pages;
use App\Filament\Resources\MappingRegionResource\RelationManagers;
use App\Models\MappingRegion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasOwnRecordPolicy;
use App\Traits\HasNavigationPolicy;

class MappingRegionResource extends Resource
{
    
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = MappingRegion::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('province_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('province_code')
                    ->maxLength(10),
                Forms\Components\TextInput::make('regency_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('regency_code')
                    ->maxLength(10),
                Forms\Components\TextInput::make('station_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('station_code')
                    ->maxLength(10),
                Forms\Components\TextInput::make('village_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('village_code')
                    ->maxLength(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('province_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('province_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('regency_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('regency_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('station_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('station_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('village_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('village_code')
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListMappingRegions::route('/'),
            'create' => Pages\CreateMappingRegion::route('/create'),
            'edit' => Pages\EditMappingRegion::route('/{record}/edit'),
        ];
    }
}
