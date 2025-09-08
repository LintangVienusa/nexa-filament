<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('middle_name')
                            ->maxLength(50),

                        TextInput::make('last_name')
                            ->maxLength(50),

                        Select::make('gender')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                            ])
                            ->required(),

                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth'),
                    ])->columns(2),

                Section::make('Employment Details')
                    ->schema([
                        DatePicker::make('date_of_joining')
                            ->label('Date of Joining')
                            ->default(now()),

                        TextInput::make('job_title')
                            ->maxLength(100),

                        TextInput::make('org_id')
                            ->numeric()
                            ->label('Organization ID'),
                    ])->columns(2),

                Section::make('Contact & Identification')
                    ->schema([
                        TextInput::make('mobile_no')
                            ->label('Mobile Number')
                            ->tel(),

                        TextInput::make('ktp_no')
                            ->label('KTP No')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('bpjs_kes_no')
                            ->label('BPJS Kesehatan No')
                            ->maxLength(20),

                        TextInput::make('bpjs_tk_no')
                            ->label('BPJS Ketenagakerjaan No')
                            ->maxLength(20),

                        TextInput::make('npwp_no')
                            ->label('NPWP No.')
                            ->maxLength(20),

                        Textarea::make('address')
                            ->rows(3),
                    ])->columns(2),

                Section::make('Additional Info')
                    ->schema([
                        Select::make('religion')
                            ->options([
                                'Islam' => 'Islam',
                                'Kristen' => 'Kristen',
                                'Katolik' => 'Katolik',
                                'Hindu' => 'Hindu',
                                'Buddha' => 'Buddha',
                                'Konghucu' => 'Konghucu',
                            ]),

                        Select::make('marital_status')
                            ->label('Status Pernikahan')
                            ->options([
                                0 => "Single",
                                1 => "Married"
                            ]),

                        TextInput::make('bank_account_name')
                            ->label('Bank Account Name')
                            ->maxLength(100),

                        TextInput::make('bank_account_no')
                            ->label('Bank Account Number')
                            ->maxLength(50),
                    ])->columns(2),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
