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
use Filament\Forms\Components\TextInput\Mask;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn\Badge;
use Filament\Tables\Columns\BooleanColumn;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\SalarySlip;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\DownloadSlipService;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;
    
    protected static ?string $permissionPrefix = 'employees';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Payroll';

    public static function canCreate(): bool
    {
        return false; 
    }

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
                        Forms\Components\Grid::make(2) 
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

                                Forms\Components\TextInput::make('employee_id')
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
                                    $set('start_date', $date->copy()->startOfMonth()->toDateString());
                                    $set('cut_off', $date->copy()->endOfMonth()->toDateString());
                                })
                                ->required()
                                ->columnSpan(6),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->default(fn () => Carbon::now()->startOfMonth()->toDateString())
                                ->afterStateHydrated(function (callable $set, $record) {
                                    if ($record?->periode) {
                                        $periode = Carbon::createFromFormat('F Y', $record->periode);
                                        $set('start_date', $periode->copy()->startOfMonth()->toDateString());
                                    }
                                })
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\DatePicker::make('cut_off')
                                ->label('Cut Off Date')
                                ->default(fn () => Carbon::now()->endOfMonth()->toDateString())
                                 ->afterStateHydrated(function (callable $set, $record) {
                                    if ($record?->periode) {
                                        $periode = Carbon::createFromFormat('F Y', $record->periode);
                                        $set('cut_off', $periode->copy()->endOfMonth()->toDateString());
                                    }
                                })
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
                                    ->label('Salary Created')
                                    ->prefix('Rp')
                                    ->readonly()
                                    ->formatStateUsing(fn($state) => $state !== null ? number_format((int)$state, 0, '.', ',') : '0')
                                    
                                    ->dehydrateStateUsing(fn($state) => (int) preg_replace('/[^0-9]/', '', (int) $state))
                                    ->required()
                                    ->reactive(),

                                Forms\Components\TextInput::make('salary_slips_approved')
                                    ->label('Salary Approved')
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, '.', ',') : '')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $number = preg_replace('/[^0-9]/', '', $state);
                                        $set('salary_slips_approved', $number === '' ? 0 : number_format((int)$number, 0, ',', '.'));
                                    })
                                    ->dehydrateStateUsing(fn($state) => preg_replace('/[^0-9]/', '', $state))
                                    ->required(),

                                

                            ])
                             
                            
                            
                    ]),


                

            
            ]);
            
    }

    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('employee_id'),
                    TextColumn::make('periode')->sortable()->searchable(),
                    TextColumn::make('status')
                                ->label('Status')
                                ->formatStateUsing(fn ($state): string => match ((int) $state) {
                                    0 => 'Draft',
                                    1 => 'Approved',
                                    2 => 'Paid',
                                    default => 'Unknown',
                                })
                                ->badge()
                                ->color(fn ($state): string => match ((int) $state) {
                                    0 => 'danger',    
                                    1 => 'success',  
                                    2 => 'info',  
                                    default => 'secondary',
                                }),
                    TextColumn::make('start_date')->date(),
                    TextColumn::make('cutoff_date')->date(),
                    TextColumn::make('salary_slips_created')
                    ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, ',', '.') : '0')
                                    ,
                    TextColumn::make('salary_slips_approved')
                    ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, ',', '.') : '0')
                                    ,
                    TextColumn::make('created_by'),
                    TextColumn::make('updated_by'),
                    TextColumn::make('created_at')->dateTime(),
                ]),

                Panel::make([
                    TextColumn::make('salary_slips')
                        ->label('Salary Slips')
                        ->getStateUsing(fn ($record) => self::renderComponentsSideBySide($record->id))
                        ->html(),
                ])
                ->collapsible(),
            ])
            
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => (int)$record->status === 0) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => 1]);
                            Notification::make()
                                ->title('Payroll Approved')
                                ->success()
                                ->send();
                                return $record->fresh();
                        }),
                Action::make('paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->visible(fn ($record) => (int)$record->status === 1) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                              $record->update(['status' => 2]);
                            Notification::make()
                                ->title('Payroll marked as Paid')
                                ->success()
                                ->send();
                                return $record->fresh();
                                
                                
                        }),
                Action::make('downloadSlip')
                        ->label('Download PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($record, DownloadSlipService $pdfService) {
                            $pdf = $pdfService->downloadSlip($record->employee_id, $record->periode);

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                "SalarySlip-{$record->employee_id}-{$record->periode}.pdf"
                            );
                        }),
                    
                
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager']))
                        ->authorize(fn () => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager'])),
                ]),
            ]);
    }

    protected static function renderComponentsSideBySide($payrollId): string
    {
        $components = SalarySlip::with('SalaryComponent')
            ->where('payroll_id', $payrollId)
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
                $id = $c->id ?? '-';
                $amount = 'Rp ' . number_format($c->amount, 0, '.', ',');

                $editUrl = \App\Filament\Resources\SalarySlipResource::getUrl('edit', ['record' => $id]);
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
            'index' => Pages\ListPayrolls::route('/'),
            // 'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }

    
}
