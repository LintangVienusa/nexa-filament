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
use Carbon\Carbon;
use App\Models\SalaryComponent;
use App\Models\Employee;
use App\Models\Payroll;

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
                                    ->disabled(fn ($record) => $record !== null)
                                    ->required(),

                                Forms\Components\TextInput::make('employee_id')
                                    ->label('Employee ID')
                                    ->disabled()
                                    ->dehydrated(false)
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
                            ]),
                            
                    ]),

               Section::make('Payroll Period')
                    ->schema([
                            Select::make('periode')
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
                                ->disabled(fn ($record) => $record !== null)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $date = Carbon::createFromFormat('F Y', $state);
                                    $set('start_date', $date->copy()->startOfMonth()->toDateString());
                                    $set('cut_off', $date->copy()->endOfMonth()->toDateString());
                                })
                                ->required()
                                ->columnSpan(6),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->afterStateHydrated(function (callable $set, $record) {
                                    if ($record?->periode) {
                                        $periode = Carbon::createFromFormat('F Y', $record->periode);
                                        $set('start_date', $periode->copy()->startOfMonth()->toDateString());
                                    }
                                })
                                ->disabled()
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\DatePicker::make('cut_off')
                                ->label('Cut Off Date')
                                ->afterStateHydrated(function (callable $set, $record) {
                                    if ($record?->periode) {
                                        $periode = Carbon::createFromFormat('F Y', $record->periode);
                                        $set('cut_off', $periode->copy()->endOfMonth()->toDateString());
                                    }
                                })
                                ->disabled()
                                ->required()
                                ->columnSpan(3),
                                
                            
                        ])
                    
                    ->columns(12),
                
                Section::make('Salary Components')
                    ->schema([
                        Repeater::make('components')
                            ->createItemButtonLabel('Add Component') // ✅ Pindahkan ke sini
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
                                            ->where('periode', $state)
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

                                Select::make('salary_component_id') ->label('Salary Component type') ->options(function () { return SalaryComponent::all()->mapWithKeys(fn($c) => [ $c->id => ($c->component_type == 0 ? 'Allowance' : 'Deduction'), ]); }) ->disabled() ->required(),

                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, '.', ',') : '')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $number = preg_replace('/[^0-9]/', '', $state);
                                        $set('amount', $number === '' ? 0 : number_format((int)$number, 0, '.', ','));
                                    })
                                    ->dehydrateStateUsing(fn($state) => preg_replace('/,/', '', $state))
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(SalarySlip::uniqueEmployee())
            ->columns([
                 
                Split::make([
                    TextColumn::make('employee_id')
                        ->label('Employee ID')
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('full_name')
                        ->label('Employee Name')
                        ->sortable()
                        ->searchable(),
                ]),

                Panel::make([
                    TextColumn::make('components')
                        ->label('Salary Components')
                         ->getStateUsing(function ($record) {
                            return SalarySlipResource::renderComponentsSideBySide($record->employee_id);
                        })
                    ->html()
                ])
                ->collapsible() 
                ->collapsed(true) 
                ->columnSpanFull()
                ->extraAttributes(['class' => '!max-w-none w-full']), 
            ])
            ->filters([
                
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
    

    protected static function renderComponentsSideBySide($employeeId)
    {
        $components = SalarySlip::with('SalaryComponent')
            ->where('employee_id', $employeeId)
            ->get();

        $allowances = $components->where('SalaryComponent.component_type', 0);
        $deductions = $components->where('SalaryComponent.component_type', 1);

        $renderTable = function($items, $typeLabel, $colorClass) {
            if ($items->isEmpty()) {
                return "<div class='text-gray-500'>{$typeLabel}: None</div>";
            }

            $total = $items->sum('amount');
            $html = "<div class='mb-4'><strong class='block mb-2'>{$typeLabel}</strong>";
            $html .= '<table class="w-full text-left border-collapse table-auto">';
            $html .= '<thead>
                        <tr>
                            <th class="px-4 py-2 text-sm border-none">Component</th>
                            <th class="px-4 py-2 text-sm border-none text-right">Amount</th>
                            <th class="px-4 py-2 text-sm border-none text-center"></th>
                        </tr>
                    </thead><tbody>';

            foreach ($items as $c) {
                $name = $c->SalaryComponent->component_name ?? '-';
                $id = $c->id?? '-';
                $amount = 'Rp ' . number_format($c->amount, 0, '.', ',');

                $editUrl = SalarySlipResource::getUrl('edit', ['record' => $id]);
                $editButton = '<a href="'.$editUrl.'" class="text-gray-700 hover:text-blue-600 inline-flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6m-3 3l3 3m0 0l3-3m-3 3V21H3V3h12v6z"/>
                                    </svg>
                                </a>';

                $html .= "<tr class='hover:bg-gray-50 transition'>
                            <td class='px-4 py-2 text-sm border-none {$colorClass}'>{$name}</td>
                            <td class='px-4 py-2 text-sm border-none text-right'>{$amount}</td>
                            <td class='px-4 py-2 text-sm border-none text-center'>{$editButton}</td>
                        </tr>";
            }

            $totalFormatted = 'Rp' . number_format($total, 0, '.', ',');
            $html .= "<tr class='font-semibold {$colorClass}'>
                        <td class='px-4 py-2 border-none'>Total</td>
                        <td class='px-4 py-2 text-right border-none'>{$totalFormatted}</td>
                        <td class='border-none'></td>
                    </tr>";

            $html .= '</tbody></table></div>';

            return $html;
        };

        $html = '<div class="flex w-full max-w-none gap-8 justify-between">';
        $html .= '<div class="flex-1">' . $renderTable($allowances, 'Allowance', 'text-green-600') . '</div>';
        $html .= '<div class="flex-1">' . $renderTable($deductions, 'Deduction', 'text-red-600') . '</div>';
        $html .= '</div>';

        return $html;
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
