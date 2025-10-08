<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Filament\Resources\ProfileResource\RelationManagers;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Profil')
                    ->schema([
                        Forms\Components\Grid::make(2) // 2 kolom
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('Nama Depan')
                                    ->disabled(),

                                Forms\Components\TextInput::make('last_name')
                                    ->label('Nama Belakang')
                                    ->disabled(),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->disabled(),

                                Forms\Components\TextInput::make('organization.divisi_name')
                                    ->label('Divisi')
                                    ->disabled()
                                    ->default(fn ($record) => $record?->organization?->divisi_name ?? '-'),

                                Forms\Components\TextInput::make('unit_name')
                                    ->label('Unit')
                                    ->disabled(),

                                Forms\Components\TextInput::make('job_title')
                                    ->label('Jabatan')
                                    ->disabled(),
                            ]),
                    ]),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
            ])
            ->bulkActions([
               
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
            'index' => Pages\ListProfiles::route('/'),
            'create' => Pages\CreateProfile::route('/create'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }
}
