<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryComponentResource\Pages;
use App\Filament\Resources\SalaryComponentResource\RelationManagers;
use App\Models\SalaryComponent;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class SalaryComponentResource extends Resource
{
     use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = SalaryComponent::class;
    
    protected $fillable = ['component_name', 'component_type'];

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Payroll Components';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('component_name')
                    ->required()
                    ->maxLength(200),
                Select::make('component_type')
                    ->options([
                        '0' => 'Allowance',
                        '1' => 'Deduction',
                    ])
                    ->required(),
                Hidden::make('permission_level')
                    ->default(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('component_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('component_type')
                    ->formatStateUsing(fn (string $state): string => $state === '0' ? 'Allowance' : 'Deduction')
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
            'index' => Pages\ListSalaryComponents::route('/'),
            'create' => Pages\CreateSalaryComponent::route('/create'),
            'edit' => Pages\EditSalaryComponent::route('/{record}/edit'),
        ];
    }
}
