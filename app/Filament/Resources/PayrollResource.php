<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use App\Models\Employee;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Payroll';

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['basic_salary'] = \App\Models\Payroll::getBasicSalary($data['employee_id']);
        $data['overtime'] = \App\Models\Payroll::calculateOvertime(
            $data['employee_id'],
            $data['period_start'],
            $data['period_end']
        );

        return $data;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Information')
                    ->schema([
                        Forms\Components\Grid::make(2) // ← Bagi jadi 2 kolom
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Employee')
                                    ->options(
                                        \App\Models\Employee::all()->mapWithKeys(fn ($e) => [
                                            $e->employee_id => $e->first_name . ' ' . $e->last_name
                                        ])
                                    )
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        

                                        if ($state) {
                                            $start = \Carbon\Carbon::parse($get('start_date'));
                                            $end = \Carbon\Carbon::parse($get('cutoff_date'));

                                            $overtime = \App\Models\Payroll::calculateOvertime($state, $start, $end);
                                            $set('overtime_pay', $overtime);
                                        }
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('employee_code')
                                    ->label('Employee ID')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Payroll Period')
                    ->schema([
                            Forms\Components\Select::make('periode')
                                ->label('Periode')
                                ->options(function () {
                                        // buat daftar periode 12 bulan terakhir
                                        $periods = [];
                                        for ($i = 0; $i < 12; $i++) {
                                            $period = Carbon::now()->subMonths($i)->format('F Y');
                                            $periods[$period] = $period;
                                        }
                                        return $periods;
                                    })
                                ->default(fn () => Carbon::now()->format('F Y'))
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $date = Carbon::createFromFormat('F Y', $state);
                                    $set('start', $date->copy()->startOfMonth()->toDateString());
                                    $set('cut_off', $date->copy()->endOfMonth()->toDateString());
                                })
                                ->required()
                                ->columnSpan(6),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->default(fn () => Carbon::now()->startOfMonth()->toDateString())
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\DatePicker::make('cut_off')
                                ->label('Cut Off Date')
                                ->default(fn () => Carbon::now()->endOfMonth()->toDateString())
                                ->required()
                                ->columnSpan(3),
                            
                        ])
                    
                    ->columns(12),

                Forms\Components\Section::make('Payroll Calculation')
                    ->description('The calculation is performed automatically based on the available data and follows the current period.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([

                                Forms\Components\TextInput::make('salary_slips_created')
                                    ->label('Net Salary')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('salary_slips_approved')
                                    ->label('Salary Approved')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Toggle::make('status')
                                    ->label('Approved')
                                    ->inline(false),

                            ]),
                    ]),


                

            
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id'),
                Tables\Columns\TextColumn::make('periode')->sortable()->searchable(),
                Tables\Columns\BooleanColumn::make('status')->label('Closed?'),
                Tables\Columns\TextColumn::make('number_of_employees'),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('cutoff_date')->date(),
                Tables\Columns\TextColumn::make('salary_slips_created'),
                Tables\Columns\TextColumn::make('salary_slips_approved'),
                Tables\Columns\TextColumn::make('created_by'),
                Tables\Columns\TextColumn::make('updated_by'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
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
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
