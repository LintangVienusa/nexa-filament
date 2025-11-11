<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Filament\Resources\ProfileResource\RelationManagers;
use App\Models\Profile;
use App\Models\Employee;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class ProfileResource extends Resource
{
    
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static ?string $model = Profile::class;
    protected static ?string $navigationIcon = null; 
    protected static ?string $navigationLabel = null; 
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected static bool $shouldRegisterNavigation = false;

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Profil')
                    ->schema([
                        Forms\Components\Grid::make(2) 
                            ->schema([
                                 Forms\Components\TextInput::make('employee_id')
                                    ->label('NIK')
                                    ->disabled(),
                               Forms\Components\TextInput::make('full_name')
                                    ->label('Nama')
                                    ->disabled()
                                    ->afterStateHydrated(function ($set, $record) {
                                        $fullName = collect([
                                            $record?->first_name,
                                            $record?->middle_name,
                                            $record?->last_name,
                                        ])->filter()->join(' ');
                                        $set('full_name', $fullName ?: '-');
                                    }),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->disabled()->reactive() 
                                    ->afterStateHydrated(function ($state, callable $set) {
                                       
                                        if (!$state) {
                                            $set('divisi_name', '-');
                                            $set('unit_name', '-');
                                            return;
                                        }

                                        $Employee = Employee::where('email', $state)->first();

                                        if (!$Employee || !$Employee->org_id) {
                                            $set('divisi_name', '-');
                                            $set('unit_name', '-');
                                            return;
                                        }

                                        $organization = Organization::find($Employee->org_id);
                                        $set('divisi_name', $organization?->divisi_name ?? '-');
                                        $set('unit_name', $organization?->unit_name ?? '-');
                                    }),

                                Forms\Components\TextInput::make('divisi_name')
                                    ->label('Divisi')
                                    ->disabled(),

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
