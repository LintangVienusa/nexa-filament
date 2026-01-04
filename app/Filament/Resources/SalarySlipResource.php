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
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use App\Models\SalaryComponent;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\HariKerjaService;
use App\Traits\HasOwnRecordPolicy;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;
use Filament\Tables\Filters\SelectFilter;


class SalarySlipResource extends Resource
{

    
     use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;

    protected static ?string $model = SalarySlip::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Payroll';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                               Select::make('divisi_name')
                                ->label('Department')
                                ->options(\App\Models\Organization::query()
                                    ->select('id', 'divisi_name')
                                    ->distinct('divisi_name')
                                    ->pluck('divisi_name', 'divisi_name'))
                                ->reactive()
                                ->required()
                                ->afterStateHydrated(function ($set, $record) {
                                    if ($record && $record->employee_id) {
                                        $employee = \App\Models\Employee::find($record->employee_id);
                                        if ($employee && $employee->organization) {
                                            $set('divisi_name', $employee->organization->divisi_name);
                                        }
                                    }
                                })
                                ->disabled(fn ($record) => $record !== null) 
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('unit_id', null);
                                }),


                                Select::make('unit_id')
                                    ->label('Unit')
                                    ->options(function (callable $get) {
                                            $departmentName = $get('divisi_name');
                                            if (!$departmentName) return [];
                                            return \App\Models\Organization::where('divisi_name', $departmentName)
                                                ->pluck('unit_name', 'id');
                                        })
                                    ->required()
                                    ->afterStateHydrated(function ($set, $record) {
                                        if ($record && $record->employee_id) {
                                            $employee = \App\Models\Employee::find($record->employee_id);
                                            if ($employee && $employee->organization) {
                                                $set('unit_id', $employee->organization->id);
                                            }
                                        }
                                    })
                                    ->disabled(fn ($record) => $record !== null) 
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('employee_name', null);
                                        $set('employee_id', null);
                                    })
                                    ->reactive(),

                                Forms\Components\Select::make('employee_name')
                                    ->label('Employee')
                                    ->options(function (callable $get) {
                                        $unitId = $get('unit_id');
                                        if (!$unitId) return [];
                                        return \App\Models\Employee::where('org_id', $unitId)
                                            ->get()
                                            ->mapWithKeys(fn ($e) => [$e->employee_id => $e->first_name . ' ' . $e->last_name]);
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->required()
                                    ->afterStateHydrated(function ($set, $record) {
                                        if ($record && $record->employee_id) {
                                            $employee = \App\Models\Employee::find($record->employee_id);
                                            if ($employee) {
                                                $set('employee_name', $employee->employee_id);
                                                $set('employee_id', $employee->employee_id);
                                            }
                                        }
                                    })
                                    ->disabled(fn ($record) => $record !== null) 
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if (!$state) return;

                                        // set Employee ID di TextInput
                                        $set('employee_id', $state);

                                        // Hitung salary components
                                        $employee = \App\Models\Employee::find($state);
                                        $basicSalary = $employee?->basic_salary ?? 0;

                                        $components = [];

                                        $basicId = \App\Models\SalaryComponent::where('component_name', 'Basic Salary')->value('id');
                                        $basicType = \App\Models\SalaryComponent::where('component_name', 'Basic Salary')->value('component_type');

                                        $components[] = [
                                            'salary_component_id' => $basicId,
                                            'component_type' => $basicType,
                                            'amount_display' => number_format((int)$basicSalary, 0, ',', '.'),
                                            'amount' => (int)$basicSalary,
                                        ];

                                        // Hari kerja & overtime
                                        $startDate = now()->startOfMonth()->toDateString();
                                        $endDate = now()->endOfMonth()->toDateString();
                                        if (now()->isSameMonth(now())) $endDate = now()->toDateString();

                                        $hariKerjaService = app(HariKerjaService::class);
                                        $hariKerjaData = $hariKerjaService->hitungHariKerja($state, $startDate, $endDate);

                                        $overtimeHours = \App\Models\Overtime::join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                                            ->where('Overtimes.employee_id', $state)
                                            ->whereBetween('Attendances.attendance_date', [$startDate, $endDate])
                                            ->sum('Overtimes.working_hours');

                                        $alphaId = \App\Models\SalaryComponent::where('component_name', 'No Attendance')->value('id');
                                        $overtimeId = \App\Models\SalaryComponent::where('component_name', 'Overtime')->value('id');

                                        // $components[] = [
                                        //     'salary_component_id' => $alphaId,
                                        //     'component_type' => \App\Models\SalaryComponent::where('component_name', 'No Attendance')->value('component_type'),
                                        //     'jumlah_hari_kerja' => $hariKerjaData['jumlah_hari_kerja'] ?? 0,
                                        //     'jumlah_absensi' => $hariKerjaData['jml_absensi'] ?? 0,
                                        //     'no_attendance' => $hariKerjaData['jml_alpha'] ?? 0,
                                        //     'amount_display' => number_format(100000, 0, ',', '.'),
                                        //     'amount' => 100000,
                                        // ];

                                        if ($overtimeHours > 0) {
                                            if($overtimeHours > 60){
                                                $overtimeHours = 60;
                                            }
                                            $components[] = [
                                                'salary_component_id' => $overtimeId,
                                                'component_type' => \App\Models\SalaryComponent::where('component_name', 'Overtime')->value('component_type'),
                                                'overtime_hours' => $overtimeHours,
                                                'amount_display' => number_format($overtimeHours > 0 ? 50000 : 0, 0, ',', '.'),
                                                 'amount' => $overtimeHours > 0 ? 50000 : 0,
                                            ];
                                        }

                                        $set('components', $components);

                                        $set('start_date', $startDate);
                                        $set('cut_off', $endDate);
                                        $set('jumlah_hari_kerja', $hariKerjaData['jumlah_hari_kerja'] ?? 0);
                                        $set('no_attendance', $hariKerjaData['jml_alpha'] ?? 0);
                                    }),

                                Forms\Components\TextInput::make('employee_id')
                                    ->label('Employee ID')
                                    ->readonly()
                                    ->dehydrated(true)
                                    ->reactive()
                                    ->required(),
                            ]),
                            
                    ]),

               Section::make('Payroll Period')
                    ->schema([
                            Select::make('periode')
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
                                ->disabled(fn (?SalarySlip $record) => $record !== null)
                                ->reactive()
                                
                                ->required()
                                ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                    
                                    $employeeId = $get('employee_id');
                                    if (!$employeeId) return;

                                    $date = Carbon::createFromFormat('F Y', $state);
                                    $startDate = $date->copy()->startOfMonth()->toDateString();
                                    $endDate = $date->copy()->endOfMonth()->toDateString();

                                    if ($date->isSameMonth(Carbon::today())) {
                                        $endDate = Carbon::today()->toDateString();
                                    }

                                    $set('start_date', $startDate);
                                    $set('cut_off', $endDate);

                                    $hariKerjaService = app(HariKerjaService::class);
                                    $hariKerjaData = $hariKerjaService->hitungHariKerja($employeeId, $startDate, $endDate);

                                    $set('jumlah_hari_kerja', $hariKerjaData['jumlah_hari_kerja'] ?? 0);
                                    $set('no_attendance', $hariKerjaData['jml_alpha'] ?? 0);

                                    $components = $get('components') ?? [];
                                    $alphaId = SalaryComponent::where('component_name', 'No Attendance')->value('id');

                                    $found = false;
                                    $amountpt =  number_format((int) 1000000, 0, ',', '.');

                                    $overtimeHours = \App\Models\Overtime::join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                                                        ->where('Overtimes.employee_id', $employeeId)
                                                        ->whereBetween('Attendances.attendance_date', [$startDate, $endDate])
                                                        ->sum('Overtimes.working_hours');

                                            $overtimeId = SalaryComponent::where('component_name', 'Overtime')->value('id');

                                            $found = false;
                                            $foundOvertime = false;
                                            $amountover = 50000;

                                    foreach ($components as &$c) {
                                        if ($c['salary_component_id'] == $alphaId) {
                                            $c['jumlah_hari_kerja'] = $hariKerjaData['jumlah_hari_kerja'] ?? 0;
                                            $c['jumlah_absensi'] = $hariKerjaData['jml_absensi'] ?? 0;
                                            $c['no_attendance'] = $hariKerjaData['jml_alpha'] ?? 0;
                                            $c['amount_display'] = number_format((int) $amountpt, 0, ',', '.') ?? 0;
                                            $c['amount'] = (int) $amountpt ?? 0;
                                            $found = true;
                                        }

                                         if ($c['salary_component_id'] == $overtimeId) {
                                                            $c['overtime_hours'] = $overtimeHours;
                                                            $foundOvertime = true;
                                                        }

                                        
                                    }

                                    if (!$found) {
                                        // $components[] = [
                                        //     'salary_component_id' => $alphaId,
                                        //     'component_type' => SalaryComponent::where('component_name', 'No Attendance')->value('component_type'),
                                        //     'jumlah_hari_kerja' => $hariKerjaData['jumlah_hari_kerja'] ?? 0,
                                        //     'jumlah_absensi' => $hariKerjaData['jml_absensi'] ?? 0,
                                        //     'no_attendance' => $hariKerjaData['jml_alpha'] ?? 0,
                                        //     'amount_display' => number_format((int) $amountpt, 0, ',', '.'),
                                        //         'amount'     => (int) $amountpt,
                                        // ];
                                    }

                                    if (!$foundOvertime && $overtimeHours > 0) {
                                                    $components[] = [
                                                        'salary_component_id' => $overtimeId,
                                                        'component_type' => SalaryComponent::where('component_name', 'Overtime')->value('component_type'),
                                                        'overtime_hours' => $overtimeHours ?? 0,
                                                        'amount_display' => number_format((int) ($overtimeHours > 0 ? $amountover : 0), 0, ',', '.'),
                                                        'amount' => (int) ($overtimeHours > 0 ? $amountover : 0),
                                                    ];
                                                }


                                    $set('components', $components);
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $employeeId = $get('employee_id');
                                    if (!$employeeId) return;

                                    $date = Carbon::createFromFormat('F Y', $state);
                                    $startDate = $date->copy()->startOfMonth()->toDateString();
                                    $endDate = $date->copy()->endOfMonth()->toDateString();

                                    
                                    if ($date->isSameMonth(Carbon::today())) {
                                        $endDate = Carbon::today()->toDateString();
                                    }

                                    $set('start_date', $startDate);
                                    $set('cut_off', $endDate);

                                    $hariKerjaService = app(HariKerjaService::class);
                                    $hariKerjaData = $hariKerjaService->hitungHariKerja($employeeId, $startDate, $endDate);

                                    $set('jumlah_hari_kerja', $hariKerjaData['jumlah_hari_kerja'] ?? 0);
                                    $set('no_attendance', $hariKerjaData['jml_alpha'] ?? 0);

                                       $overtimeHours = \App\Models\Overtime::join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                                                        ->where('Overtimes.employee_id', $employeeId)
                                                        ->whereBetween('Attendances.attendance_date', [$startDate, $endDate])
                                                        ->sum('Overtimes.working_hours');

                                            $overtimeId = SalaryComponent::where('component_name', 'Overtime')->value('id');

                                            $found = false;
                                            $foundOvertime = false;
                                            $amountover = 50000;

                                   

                                         $components = $get('components') ?? [];
                                        $alphaId = SalaryComponent::where('component_name', 'No Attendance')->value('id');

                                        $found = false;
                                        $amountpt = 100000;

                                        foreach ($components as &$c) {
                                            if ($c['salary_component_id'] == $alphaId) {
                                                $c['jumlah_hari_kerja'] = $hariKerjaData['jumlah_hari_kerja'] ?? 0;
                                                $c['jumlah_absensi'] = $hariKerjaData['jml_absensi'] ?? 0;
                                                $c['no_attendance'] = $hariKerjaData['jml_alpha'] ?? 0;
                                                $c['amount_display'] = number_format((int) $amountpt, 0, ',', '.') ?? 0;
                                                $c['amount'] = (int) $amountpt ?? 0;
                                                $found = true;
                                            }

                                             if ($c['salary_component_id'] == $overtimeId) {
                                                            $c['overtime_hours'] = $overtimeHours;
                                                            $foundOvertime = true;
                                                        }
                                        }

                                        if (!$found) {
                                            // $components[] = [
                                            //     'salary_component_id' => $alphaId,
                                            //     'component_type' => SalaryComponent::where('component_name', 'No Attendance')->value('component_type'),
                                            //     'jumlah_hari_kerja' => $hariKerjaData['jumlah_hari_kerja'] ?? 0,
                                            //     'jumlah_absensi' => $hariKerjaData['jml_absensi'] ?? 0,
                                            //     'no_attendance' => $hariKerjaData['jml_alpha'] ?? 0,
                                            //     'amount_display' => number_format((int) $amountpt, 0, ',', '.'),
                                            //         'amount'     => (int) $amountpt,
                                            // ];
                                        }

                                        if (!$foundOvertime && $overtimeHours > 0) {
                                                    $components[] = [
                                                        'salary_component_id' => $overtimeId,
                                                        'component_type' => SalaryComponent::where('component_name', 'Overtime')->value('component_type'),
                                                        'overtime_hours' => $overtimeHours ?? 0,
                                                        'amount_display' => number_format((int) ($overtimeHours > 0 ? $amountover : 0), 0, ',', '.'),
                                                        'amount' => (int) ($overtimeHours > 0 ? $amountover : 0),
                                                    ];
                                                }


                                        $set('components', $components);
                                })
                                ->columnSpan(6),


                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->default(fn () => now()->subMonthNoOverflow()->day(5)->startOfDay()->toDateString())
                                ->afterStateHydrated(function (callable $set, $record) {
                                    if ($record?->periode) {
                                        $periode = Carbon::createFromFormat('F Y', $record->periode);
                                        $set('start_date', $periode->copy()->subMonthNoOverflow()->day(5)->toDateString());
                                    }
                                })
                                ->disabled()
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\DatePicker::make('cut_off')
                                ->label('Cut Off Date')
                                ->default(fn () => now()->day(4)->endOfDay()->toDateString())
                                ->afterStateHydrated(function (callable $set, $record) {
                                    if ($record?->periode) {
                                        $periode = Carbon::createFromFormat('F Y', $record->periode);
                                        $set('cut_off', $periode->copy()->day(4)->toDateString());
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
                        
                            ->schema([
                                Select::make('salary_component_id')
                                    ->label('Salary Component')
                                    ->options(function ($record) {
                                            $query = SalaryComponent::query();

                                            // Kalau edit record (tidak kosong), exclude komponen tertentu
                                            if ($record == '') {
                                                $query->whereNotIn('component_name', [
                                                    // 'BPJS Kesehatan',
                                                    // 'JHT BPJS TK',
                                                    // 'JP BPJS TK',
                                                    // 'JKK BPJS TK',
                                                    // 'JKM BPJS TK',
                                                    // 'Marriage Allowance',
                                                    // 'Child Allowance',
                                                    // 'PPh 21',
                                                ]);
                                            }

                                            return $query->get()
                                                ->mapWithKeys(fn($c) => [
                                                    $c->id => $c->component_name ?? 'No Name'
                                                ]);
                                    })
                                    // ->rules([
                                    //     function (callable $get, $record) {
                                    //         return \Illuminate\Validation\Rule::unique('SalaryComponents', 'id')
                                    //             ->where('employee_id', $get('employee_id'))
                                    //             ->ignore($record?->id);
                                    //     },
                                    // ])
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $employeeId = $get('employee_id');
                                        if (!$employeeId || !$state) return;

                                        $scomponent = SalaryComponent::find($state);
                                            if ($scomponent) {
                                                $set('component_type', $scomponent->component_type);
                                            }

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

                                Select::make('component_type')
                                        ->label('Salary Component type')
                                        ->options([
                                                0 => 'Allowance',
                                                1 => 'Deduction',
                                            ])
                                            ->disabled() 
                                            ->required(),
                                
                                // TextInput::make('jumlah_hari_kerja')
                                //     ->label('Jumlah Hari Kerja')
                                //     ->reactive()
                                //     ->visible(function ($get) {
                                //         $basicSalaryId = \App\Models\SalaryComponent::where('component_name', 'No Attendance')->value('id');
                                        
                                //         return $get('salary_component_id') == $basicSalaryId;
                                //     })
                                //     ->required(),
                                // TextInput::make('jumlah_absensi')
                                //     ->label('Jumlah Absensi')
                                //     ->reactive()
                                //     ->required()
                                //     ->visible(function ($get) {
                                //         $basicSalaryId = SalaryComponent::where('component_name', 'No Attendance')->value('id');
                                //         return $get('salary_component_id') == $basicSalaryId;
                                //     }),
                                // TextInput::make('no_attendance')
                                //     ->label('Jumlah Alpha')
                                //     ->reactive()
                                //     ->required()
                                //     ->visible(function ($get) {
                                //         $basicSalaryId = SalaryComponent::where('component_name', 'No Attendance')->value('id');
                                //         return $get('salary_component_id') == $basicSalaryId;
                                //     }),
                                TextInput::make('overtime_hours')
                                    ->label('Overtime Hours')
                                    ->reactive()
                                    ->required()
                                    ->visible(function ($get) {
                                        $basicSalaryId = SalaryComponent::where('component_name', 'Overtime')->value('id');
                                        return $get('salary_component_id') == $basicSalaryId;
                                    }),

                                Forms\Components\TextInput::make('amount_display')
                                    ->label(function ($get) {
                                        $componentId = $get('salary_component_id');
                                        // $potonganAlphaId = SalaryComponent::where('component_name', 'No Attendance')->value('id');
                                        $overtimeId = SalaryComponent::where('component_name', 'Overtime')->value('id');

                                        // if ($componentId == $potonganAlphaId) {
                                        //     return 'Amount (*day)';
                                        // } else
                                        if ($componentId == $overtimeId) {
                                            return 'Amount (*hour)';
                                        }

                                        return 'Amount';
                                    })
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->formatStateUsing(fn($state) => $state !== null ? number_format((int)$state, 0, ',', '.') : '0')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $number = preg_replace('/[^0-9]/', '', $state);
                                        $amount = $number !== '' ? (int)$number : 0;
                                        $set('amount_display', number_format($amount, 0, ',', '.'));
                                        $set('amount', $amount);
                                    })
                                    ->dehydrateStateUsing(fn($state) => preg_replace('/[^0-9]/', '', $state))
                                    ->required(),

                                Forms\Components\Hidden::make('amount')
                                    ->default(0)
                                    ->required()
                                    ->dehydrated(true),

                                
                            ])
                            ->columns(2)
                            
                            
                    ])
                    ->columns(1)
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(
                SalarySlip::query()
                    ->select('employee_id', 'periode')
                    ->selectRaw('MAX(id) as id') 
                    ->groupBy('employee_id', 'periode')
            )
            ->defaultSort('id', 'desc')
            ->columns([
                 
                Split::make([
                    TextColumn::make('employee_id')
                        ->label('Employee ID')
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('employee.first_name')
                        ->label('Nama')
                        ->getStateUsing(fn($record) => $record->employee?->full_name ?? '-')
                        ->searchable(query: function ($query, $search) {
                            $query->whereHas('employee', function ($q) use ($search) {
                                $q->whereRaw("CONCAT(first_name, ' ', middle_name,' ', last_name) LIKE ?", ["%{$search}%"]);
                            });
                        })
                        ->sortable(function (Builder $query) {
                            $direction = request()->input('tableSortDirection', 'asc');
                            return $query->orderBy(
                                Employee::selectRaw("CONCAT(first_name,' ', middle_name, ' ', last_name)")
                                    ->whereColumn('employees.employee_id', 'attendances.employee_id')
                                    ->limit(1),
                                $direction
                            );
                        }),

                    TextColumn::make('periode')
                        ->label('Payroll Periode')
                        ->sortable()
                        ->searchable(),
                ]),

                Panel::make([
                    TextColumn::make('components')
                        ->label('Salary Components')
                         ->getStateUsing(function ($record) {
                            return SalarySlipResource::renderComponentsSideBySide($record->employee_id,
                            $record->periode);
                        })
                    ->html()
                ])
                ->collapsible() 
                ->collapsed(true) 
                ->columnSpanFull()
                ->extraAttributes(['class' => '!max-w-none w-full']), 
            ])
            ->filters([
                SelectFilter::make('periode')
                    ->label('Filter Periode')
                    ->options(
                        \App\Models\SalarySlip::query()
                            ->select('periode')
                            ->distinct()
                            ->orderBy('periode', 'desc')
                            ->pluck('periode', 'periode')
                            ->toArray()
                    )
            ])
            ->actions([
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
    

    protected static function renderComponentsSideBySide($employeeId,$periode)
    {
        $components = SalarySlip::with('SalaryComponent')
            ->where('employee_id', $employeeId)
            ->where('periode', $periode)
            ->get();

        $allowances = $components->where('SalaryComponent.component_type', 0);
        $deductions = $components->where('SalaryComponent.component_type', 1);
        
        $totalAllowances = $allowances->sum('amount');
        $totalDeductions = $deductions->sum('amount');
        $totalpay = ($totalAllowances - $totalDeductions);

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
                $amount = 'Rp ' . number_format($c->amount, 0, ',', '.');
                if ($id) {
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
                }else{
                    $editButton = '';
                }
            }

            $totalFormatted = 'Rp' . number_format($total, 0, ',', '.');
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
        
            $totalpayFormatted = 'Rp' . number_format($totalpay, 0, ',', '.');
        $html .= "<table><tr>
                        <td class='px-4 py-2 border-none'><b>Total Pay</td>
                        <td class='px-4 py-2 text-right border-none'>".$totalpayFormatted."</b></td>
                        <td class='border-none'></td>
                    </tr></table>";
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
