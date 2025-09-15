<?php

namespace App\Filament\Resources;


use App\Filament\Resources\SalarySlipResource\Pages;
use App\Filament\Resources\SalarySlipResource\RelationManagers;
use App\Models\SalarySlip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn\Badge;
use Filament\Notifications\Notification;
use App\Models\SalaryComponent;
use App\Models\Employee;

class SalarySlipResource extends Resource
{
    protected static ?string $model = SalarySlip::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Salary Slip';

    

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
                                    ->required(),

                                Forms\Components\TextInput::make('employee_id')
                                    ->label('Employee ID')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
                Section::make('Salary Components')
                    // ->schema([
                        // Repeater::make('components')
                            ->schema([
                                Select::make('salary_component_id')
                                    ->label('Salary Component')
                                    ->options(function () {
                                        return SalaryComponent::all()->mapWithKeys(fn($c) => [
                                            $c->id => $c->component_name ?? 'No Name'
                                        ]);
                                    })
                                    ->rules([
                                        function (callable $get, $record) {
                                            return \Illuminate\Validation\Rule::unique('salary_component_id', 'salary_component_id')
                                                ->where('employee_id', $get('employee_id'))
                                                ->ignore($record?->id);
                                        },
                                    ])
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $employeeId = $get('employee_id');
                                        if (!$employeeId || !$state) return;

                                        $exists = SalarySlip::where('employee_id', $employeeId)
                                            ->where('salary_component_id', $state)
                                            ->exists();

                                        if ($exists) {
                                            Notification::make()
                                                ->title('Duplicate Entry')
                                                ->body('This salary component has already been assigned to the selected employee.')
                                                ->danger()
                                                ->send();

                                                $set('salary_component_id', null);
                                        }
                                    })
                                    ->required(),

                                Select::make('salary_component_id')
                                    ->label('Salary Component type')
                                    ->options(function () {
                                        return SalaryComponent::all()->mapWithKeys(fn($c) => [
                                            $c->id => ($c->component_type == 0 ? 'Allowance' : 'Deduction'),
                                        ]);
                                    })
                                    ->disabled()
                                    ->required(),

                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->formatStateUsing(function ($state) {
                                        return $state ? number_format((int) $state, 0, '.', ',') : '';
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $number = preg_replace('/[^0-9]/', '', $state);
                                        // $set('amount', $numeric === '' ? 0 : (int) $numeric);
                                        $set('amount', $number === '' ? 0 : number_format((int) $number, 0, '.', ','));
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        return preg_replace('/\,/', '', $state);
                                    })
                                    ->required(),
                            ])
                            ->columns(2),
                    // ])
                    // ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('salaryComponent.component_name')
                    ->label('Salary Components')
                    ->listWithLineBreaks() 
                    ->limit(50), 
                Tables\Columns\TextColumn::make('salaryComponent.component_type')
                    ->label('Salary Components Type')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Allowance' : 'Deduction')
                    ->listWithLineBreaks() 
                    ->limit(50), 
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListSalarySlips::route('/'),
            'create' => Pages\CreateSalarySlip::route('/create'),
            'edit' => Pages\EditSalarySlip::route('/{record}/edit'),
        ];
    }
}
