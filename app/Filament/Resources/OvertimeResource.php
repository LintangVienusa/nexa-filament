<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeResource\Pages;
use App\Filament\Resources\OvertimeResource\RelationManagers;
use App\Models\Overtime;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Timesheet;
use App\Models\Organization;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasNavigationPolicy;

class OvertimeResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;

    protected static ?string $model = Overtime::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    // protected static ?string $permissionPrefix = 'employees';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Overtimes';

    public static function form(Form $form): Form
    {
        $currentUser = auth()->user();
        $jobTitle = $currentUser->employee?->job_title;
        $orgId = $currentUser->employee?->org_id;
        $employeeId = $currentUser->employee?->employee_id;
        return $form
            ->schema([
                Forms\Components\Select::make('divisi_id')
                    ->label('Divisi')
                    ->options(Organization::pluck('divisi_name', 'divisi_name'))
                    ->visible(fn() => in_array($jobTitle, ['VP','CEO','CTO']))
                    ->reactive(),
                Forms\Components\Select::make('unit_id')
                    ->label('Unit')
                    ->options(function ($get) {
                        $divisi_name = $get('divisi_id');
                        if ($divisi_name) {
                            return Organization::where('divisi_name', $divisi_name)->pluck('unit_name', 'id');
                        }
                        return [];
                    })
                    ->visible(fn() => in_array($jobTitle, ['VP','CEO','CTO']))
                    ->reactive(),
                Forms\Components\Select::make('employee_id')
                    ->label('Employee')
                    ->options(function ($get) use ($jobTitle, $orgId, $employeeId) {
                        $currentUser = auth()->user();
                        $jobTitle = $currentUser->employee?->job_title;

                        $query = Employee::query();

                        if ($jobTitle === 'Manager' || $jobTitle === "SVP") {
                            
                            $query->where('org_id', $orgId)
                                ->where('job_title', 'Staff');
                        } elseif ($jobTitle === 'Staff') {
                            $query->where('employee_id', $employeeId);
                        }elseif (in_array($jobTitle, ['VP','CEO','CTO'])) {
                            $unitId = $get('unit_id');
                            if ($unitId) {
                                $query->where('org_id', $unitId);
                            }
                        }

                        return $query->get()->pluck('full_name', 'employee_id')->toArray();
                    })
                    ->searchable()
                    ->required()
                     ->getOptionLabelUsing(fn($value) => Employee::find($value)?->full_name ?? $value)
    
                    ->default(fn($component) => auth()->user()->employee?->employee_id)
                    ->afterStateHydrated(function ($component, $state) {
                        if ($state) {
                            $employee = Employee::find($state);
                            if ($employee) {
                                $component->placeholder($employee->full_name);
                            }
                        }
                    })
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $employeeId = $state;
                            $overtimeDate = $get('overtime_date');


                            $todayf = Carbon::parse($overtimeDate, 'Asia/Jakarta');

                            if ($todayf->day >= 28) {
                                $startPeriod = $todayf->copy()->day(28)->startOfDay();
                                $endPeriod   = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
                            } else {
                                $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
                                $endPeriod   = $todayf->copy()->day(27)->endOfDay();
                            }

                            $totalOvertime = Overtime::query()
                                ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                                ->where('Overtimes.employee_id', $employeeId)
                                ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
                                ->sum('Overtimes.working_hours');

                            $remainingMinutes = max(0, (60 * 60) - ($totalOvertime * 60));
                            $hours = intdiv($remainingMinutes, 60);
                            $minutes = $remainingMinutes % 60;

                            $set('remaining_overtime', sprintf('%02d jam %02d menit', $hours, $minutes));
                        })
                    ->disabled(fn() => $jobTitle === 'Staff')
                    ->dehydrated(true)
                    ->reactive(),

                Forms\Components\DatePicker::make('overtime_date')
                    ->label('Overtime Date')
                    ->required()
                    ->rule(function ($get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $employeeId = $get('employee_id');
                            if ($employeeId && $value) {
                                $exists = Attendance::where('employee_id', $employeeId)
                                    ->whereDate('attendance_date', $value)
                                    ->exists();

                                if (! $exists) {
                                    $fail("The selected date is not available in attendance.");
                                }
                            }
                        };
                    })
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $employeeId = $get('employee_id');
                        if ($employeeId && $state) {
                            $attendanceId = Attendance::where('employee_id', $employeeId)
                                ->whereDate('attendance_date', $state)
                                ->value('id');

                            $set('attendance_id', $attendanceId);
                        } else {
                            $set('attendance_id', null);
                        }
                    }),
                Forms\Components\Hidden::make('attendance_id')
                    ->default(fn () => Attendance::where('employee_id', auth()->user()->employee?->employee_id)
                    ->latest('id')
                    ->value('id')),

                Forms\Components\TimePicker::make('start_time')
                    ->label('Start Time')
                    ->reactive()
                    ->format('H:i')
                    ->seconds(false)
                    ->required()
                    ->default('18:00')
                    ->rules(['after_or_equal:17:00', 'before_or_equal:18:00'])
                    ->afterStateHydrated(function ($state, $set) {
                        if (!$state) {
                            $set('start_time', Carbon::createFromTime(18, 0, 0)->format('H:i'));
                        }
                    })
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $end = $get('end_time');
                        if ($state && $end) {
                            $start = Carbon::createFromFormat('H:i', $state);
                            $e = Carbon::createFromFormat('H:i', $end);
                            if ($e->lessThan($start)) $e->addDay();
                            $minutes = $start->diffInMinutes($e);

                            // Hitung total jam dan menit
                            $hours = floor($minutes / 60);
                            $mins  = $minutes % 60;
                            $formatted = sprintf('%02d jam %02d menit', $hours, $mins);

                            $set('working_hours', $formatted);
                        }
                    }),

                Forms\Components\TimePicker::make('end_time')
                    ->label('End Time')
                    ->reactive()
                    ->format('H:i')
                    ->seconds(false)
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $start = $get('start_time');
                        $employeeId = $get('employee_id');
                        $overtimeDate = $get('overtime_date');

                        if ($start && $state) {
                            $s = Carbon::createFromFormat('H:i', $start);
                            $end = Carbon::createFromFormat('H:i', $state);

                            // Jika end time lebih kecil dari start, tambahkan 1 hari
                            if ($end->lessThan($s)) {
                                $end->addDay();
                            }

                            $minutes = $s->diffInMinutes($end);
                            $hours = $minutes / 60;
                            $set('working_hours', sprintf('%02d jam %02d menit', intdiv($minutes, 60), $minutes % 60));

                            // 🔥 Logika tambahan: batasi durasi jika total lembur sudah > 50 jam
                            if ($employeeId && $overtimeDate) {
                                $todayf = Carbon::parse($overtimeDate, 'Asia/Jakarta');

                                // Tentukan periode aktif (28 -> 27)
                                if ($todayf->day >= 28) {
                                    $startPeriod = $todayf->copy()->day(28)->startOfDay();
                                    $endPeriod   = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
                                } else {
                                    $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
                                    $endPeriod   = $todayf->copy()->day(27)->endOfDay();
                                }

                                // Total lembur periode berjalan
                                $totalOvertime = Overtime::query()
                                    ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                                    ->where('Overtimes.employee_id', $employeeId)
                                    ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
                                    ->sum('Overtimes.working_hours');

                                if ($totalOvertime > 50) {
                                     $start = Carbon::parse($get('start_time'));
                                    $sisa = 60-$totalOvertime;
                                    $maxEnd = $start->copy()->addHours($sisa);

                                    // Jika end lebih dari 10 jam dari start
                                    if ($end->greaterThan($maxEnd)) {
                                        $endnew = $maxEnd->format('H:i');
                                        $set('end_time', $maxEnd->format('H:i'));
                                        $minutes = $s->diffInMinutes($endnew);
                                        $hours = $minutes / 60;
                                        $set('working_hours', sprintf('%02d jam %02d menit', intdiv($minutes, 60), $minutes % 60));

                                        Notification::make()
                                            ->title('Maksimal durasi lembur 60 jam')
                                            ->warning()
                                            ->send();
                                    }
                                    
                                    $set('remaining_overtime', $sisa);
                                }
                            }
                        }
                    }),

                Forms\Components\TextInput::make('remaining_overtime')
                    ->label('Sisa Saldo Overtime')
                    ->disabled()
                    ->reactive()
                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                        $employeeId = $get('employee_id');
                        $overtimeDate = $get('overtime_date');

                        // if (! $employeeId || ! $overtimeDate) {
                        //     $set('remaining_overtime', 70);
                        //     return;
                        // }

                        $todayf = Carbon::parse($overtimeDate, 'Asia/Jakarta');

                        if ($todayf->day >= 28) {
                            $startPeriod = $todayf->copy()->day(28)->startOfDay();
                            $endPeriod   = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
                        } else {
                            $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
                            $endPeriod   = $todayf->copy()->day(27)->endOfDay();
                        }

                        // Hitung total overtime pada periode
                        $totalOvertime = Overtime::query() 
                            ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                            ->where('Overtimes.employee_id', $employeeId)
                            ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
                            ->sum('Overtimes.working_hours');

                        $totalMinutes = $totalOvertime * 60;
                        $remainingMinutes = max(0, (60 * 60) - $totalMinutes); // 60 jam dikonversi ke menit

                        // Ubah ke format "xx jam xx menit"
                        $hours = intdiv($remainingMinutes, 60);
                        $minutes = $remainingMinutes % 60;
                        $formatted = sprintf('%02d jam %02d menit', $hours, $minutes);

                        $set('remaining_overtime', $formatted);
                    })
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $employeeId = $get('employee_id');
                        $overtimeDate = $get('overtime_date');

                        if (! $employeeId || ! $overtimeDate) {
                            $set('remaining_overtime', '-');
                            return;
                        }

                        $todayf = Carbon::parse($overtimeDate, 'Asia/Jakarta');

                        if ($todayf->day >= 28) {
                            $startPeriod = $todayf->copy()->day(28)->startOfDay();
                            $endPeriod   = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
                        } else {
                            $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
                            $endPeriod   = $todayf->copy()->day(27)->endOfDay();
                        }

                        $totalOvertime = Overtime::query()
                            ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                            ->where('Overtimes.employee_id', $employeeId)
                            ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
                            ->sum('Overtimes.working_hours');

                        $remainingMinutes = max(0, (60 * 60) - ($totalOvertime * 60));
                        $hours = intdiv($remainingMinutes, 60);
                        $minutes = $remainingMinutes % 60;

                        $set('remaining_overtime', sprintf('%02d jam %02d menit', $hours, $minutes));
                    }),


                Forms\Components\TextInput::make('working_hours')
                    ->label('Working Hours')
                    ->disabled()
                    ->reactive()
                    ->required()
                    ->afterStateHydrated(function ($state, $set, $get) {
                        $start = $get('start_time');
                        $end = $get('end_time');
                        if ($start && $end) {
                            // $s = Carbon::createFromFormat('H:i:s', $start);
                            // $e = Carbon::createFromFormat('H:i:s', $end);
                            $s = Carbon::parse($start);
                            $e = Carbon::parse($end);
                            if ($e->lessThan($s)) $e->addDay();
                            $minutes = $s->diffInMinutes($e);

                            $hours = floor($minutes / 60);
                            $mins  = $minutes % 60;
                            $formatted = sprintf('%02d jam %02d menit', $hours, $mins);

                            $set('working_hours', $formatted);
                        }
                    })
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $employeeId = $get('employee_id');
                        $overtimeDate = $get('overtime_date');
                        $todayf = Carbon::parse($overtimeDate, 'Asia/Jakarta');

                        // Ambil angka dari teks (misalnya "02 jam 30 menit" → 2.5)
                        if (preg_match('/(\d+)\s*jam\s*(\d+)\s*menit/', $state, $match)) {
                            $overtimeHours = (int)$match[1] + ((int)$match[2] / 60);
                        } else {
                            $overtimeHours = 0;
                        }

                        if ($employeeId && $overtimeDate) {
                            if ($todayf->day >= 28) {
                                $startPeriod = $todayf->copy()->day(28)->startOfDay();
                                $endPeriod   = $todayf->copy()->addMonthNoOverflow()->day(27)->endOfDay();
                            } else {
                                $startPeriod = $todayf->copy()->subMonthNoOverflow()->day(28)->startOfDay();
                                $endPeriod   = $todayf->copy()->day(27)->endOfDay();
                            }

                            $totalOvertime = Overtime::query()
                                ->join('Attendances', 'Overtimes.attendance_id', '=', 'Attendances.id')
                                ->where('Overtimes.employee_id', $employeeId)
                                ->whereBetween('Attendances.attendance_date', [$startPeriod, $endPeriod])
                                ->sum('Overtimes.working_hours');

                            $remaining = 60 - ($totalOvertime + $overtimeHours);
                            $set('remaining_overtime', max($remaining, 0));

                            if (($totalOvertime + $overtimeHours) > 60) {
                                Notification::make()
                                    ->title('Total overtime tidak boleh lebih dari 60 jam!')
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),

            

                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Select::make('job_id')
                    ->label('Job')
                    ->relationship('job', 'job_description',function ($query, $get) {
                        $nik = $get('employee_id'); 
                        $overtimeDate = $get('overtime_date');
                        if ($nik && $overtimeDate) {
                             $query->whereHas('timesheet.attendance', function ($q) use ($nik, $overtimeDate) {
                                    $q->where('employee_id', $nik)
                                    ->whereDate('attendance_date', $overtimeDate);
                                });
                        } else {
                            $query->whereRaw('0 = 1');
                        }
                    })
                    ->preload()
                    ->searchable(),
                
                FileUpload::make('ba_file')
                        ->label('Upload Berita Acara (BA)')
                        ->directory('overtimes/ba')
                        ->downloadable()
                        ->openable()
                        ->previewable(true)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'image/jpeg',
                            'image/png',
                        ])
                        ->maxSize(10240)
                        ->helperText('Hanya bisa diinput oleh user di atas Manager.')
                        ->visible(function () {
                            $user = auth()->user();
                            $jobTitle = $user->employee->job_title ?? null;
                            $allowedJobTitles = [
                                'Manager',
                                // 'VP',
                                // 'CEO',
                                // 'CTO',
                            ];
                            return in_array($jobTitle, $allowedJobTitles);
                        }),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn() => auth()->user()->email)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
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
                Tables\Columns\TextColumn::make('employee.employee_id')->label('NIK')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('job.job_description')->label('Pekerjaan')->sortable(),
                Tables\Columns\TextColumn::make('description')->label('keterangan')->sortable(),
                Tables\Columns\TextColumn::make('working_hours')->label('Jam')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->time()->sortable(),
                Tables\Columns\TextColumn::make('end_time')->time()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => 'Draft',
                        1 => 'Approve',
                        default => $state,
                    })
                    ->color(fn ($state): string => match ($state) {
                        0 => 'warning',
                        1 => 'success',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')->label('dibuat oleh')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_by')->label('disetujui oleh')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('updated at')->dateTime()->sortable(),
            ])
            ->actions([
               Actions\Action::make('approved')
                        ->label('Approve')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => (int)$record->status === 0  && ! auth()->user()->isStaff()) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $type = $record->leave_type;
                            $record->update(['status' => 1]);
                            Notification::make()
                                ->title( $type .' Approve')
                                ->success()
                                ->send();
                                return $record->fresh();
                        }),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->status != 1),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make()
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
            'index' => Pages\ListOvertimes::route('/'),
            'create' => Pages\CreateOvertime::route('/create'),
            'edit' => Pages\EditOvertime::route('/{record}/edit'),
        ];
    }
}
