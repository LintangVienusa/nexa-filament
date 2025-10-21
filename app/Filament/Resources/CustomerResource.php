<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $permissionPrefix = 'employees';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Mitra Info')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Mitra Name')
                            ->required()
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // otomatis buat inisial
                                $words = explode(' ', $state);
                                $initials = '';
                                foreach ($words as $word) {
                                    $initials .= strtoupper(substr($word, 0, 1));
                                }
                                $set('initial', $initials);
                            })
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->required()
                            ->reactive(),

                        TextInput::make('initial')
                            ->label('Initial')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->required()
                            ->reactive()
                            ->maxLength(10),
                        
                        Textarea::make('address')
                            ->required()
                            ->rows(3)
                            ->maxLength(255),
                    ]),

                Section::make('Contact Info')
                    ->schema([
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Nomor phone')
                            ->tel()
                            ->numeric()
                            ->maxLength(16)
                            // ->helperText('Masukan 08xxxxxxxxxx')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mitra_name')
                    ->searchable(),
                TextColumn::make('initial')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
