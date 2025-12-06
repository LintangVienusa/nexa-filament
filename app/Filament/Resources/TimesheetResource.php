<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimesheetResource\Pages;
use App\Filament\Resources\TimesheetResource\RelationManagers\JobsRelationManager;
use App\Models\Attendance;
use App\Models\Timesheet;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Traits\HasPermissions;
use Filament\Forms\Components\Select;
use App\Traits\HasNavigationPolicy;

class TimesheetResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy,HasNavigationPolicy;
    
    protected static ?string $model = Timesheet::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $permissionPrefix = 'employees';
    protected static string $ownerColumn = 'email';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Timesheets';

    public static function form(Form $form): Form
    {
        return $form->extraAttributes(['wire:key' => 'timesheet-form'])
        ->schema([
            Hidden::make('employee_id')
                ->default(fn () => auth()->user()->employee?->employee_id), 

            DatePicker::make('timesheet_date')
                ->label('Tanggal')
                ->required()
                ->reactive()
                ->displayFormat('Y-m-d')
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $date = $state instanceof \Carbon\Carbon ? $state->format('Y-m-d') : $state;
                    $employeeId = $get('employee_id');

                    if (! $employeeId || ! $date) {
                        $set('attendance_id', null);
                        return;
                    }

                    $attendance = Attendance::where('employee_id', $employeeId)
                        ->whereDate('attendance_date', $date)
                        ->first();

                    $set('attendance_id', $attendance?->id);
                })
                ->disabled(fn($get, $record) => $record !== null),

            TextInput::make('attendance_id')
                ->label('ID Attendance')
                ->readonly()
                ->reactive()
                ->dehydrated(true)
                ->required(),

            Section::make('Informasi Kehadiran')
                ->schema([
                    ViewField::make('attendance_id')
                        ->label('Attendance Info')
                        ->view('filament.forms.component.attendance-info')
                        ->dehydrated(false)
                        ->reactive(true),
                ]),

            // Section::make('Durasi Pekerjaan')
            //     ->schema([

            //     Grid::make(2)
            //         ->schema([
            //             TextInput::make('job_duration_hours')
            //                 ->numeric()
            //                 ->hiddenLabel()
            //                 ->default(0)
            //                 ->suffix(' jam')
            //                 ->reactive()
            //                 ->afterStateUpdated(function ($state, callable $set, $get) {
            //                     $hours = (float) $state;
            //                     $minutes = (float) $get('job_duration_minutes');
            //                     $decimal = $hours + ($minutes / 60);
            //                     $set('job_duration', round($decimal, 2));
            //                 }),

            //             TextInput::make('job_duration_minutes')
            //                 ->numeric()
            //                 ->hiddenLabel()
            //                 ->default(0)
            //                 ->suffix(' menit')
            //                 ->reactive()
            //                 ->afterStateUpdated(function ($state, callable $set, $get) {
            //                     $hours = (float) $get('job_duration_hours');
            //                     $minutes = (float) $state;
            //                     $decimal = $hours + ($minutes / 60);
            //                     $set('job_duration', round($decimal, 2));
            //                 }),
            //         ])
            //     ]),

            Hidden::make('job_duration')
                ->dehydrated(true)
                ->default(function ($record) {
                    if (! $record) {
                        return 0;
                    }

                    $createdAt = $record->created_at instanceof Carbon
                        ? $record->created_at
                        : Carbon::parse($record->created_at);

                    $now = now();

                    return $createdAt->diffInMinutes($now);
                }),

            Textarea::make('job_description')
                ->dehydrated(true)
                ->required()
                ->columnSpanFull(),

            
            Select::make('status')
                ->label('Status')
                ->dehydrated(true)
                ->options([
                    '0' => 'On Progress',
                    '1' => 'Pending',
                    '2' => 'Done',
                    '3' => 'Cancel',
                ])
                ->default('0') 
                ->required(),
            Hidden::make('created_by')
                ->default(fn () => auth()->user()->email ?? null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('attendance_id')
                ->label('Attendance')
                ->sortable()
                ->formatStateUsing(fn ($state) => "View #{$state}")
                ->url(fn ($record) => $record->attendance_id
                    ? AttendanceResource::getUrl('edit', ['record' => $record->attendance_id])
                    : null)
                ->openUrlInNewTab(),
             TextColumn::make('Attendance.employee_id')
                ->label('NIK')
                ->sortable()
                ->searchable(),
            
             TextColumn::make('attendance.employee.full_name')
                ->label('Nama')
                ->sortable(query: function ($query, string $direction) {
                    $query
                        ->leftJoin('Attendances', 'Timesheets.attendance_id', '=', 'Attendances.id')
                        ->leftJoin('Employees', 'Attendances.employee_id', '=', 'Employees.employee_id')
                        ->orderByRaw("CONCAT(Employees.first_name, ' ', Employees.middle_name,' ', Employees.last_name) {$direction}")
                        ->select('Timesheets.*');
                })
                ->searchable(query: function ($query, string $search) {
                    $query->whereHas('attendance.employee', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', middle_name,' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                }),

            TextColumn::make('Attendance.attendance_date')
                ->label('Tanggal Attendance')
                ->date()
                ->sortable(),
           

            TextColumn::make('job_description')
                ->label('Detail Pekerjaan')
                ->sortable()
                ->searchable(),

            // Tables\Columns\TextColumn::make('job_duration')
            //     ->label('Durasi Pekerjaan')
            //     ->getStateUsing(function ($record) {
            //         $hours = floor($record->job_duration);
            //         $minutes = round(($record->job_duration - $hours) * 60);

            //         if ($hours > 0 && $minutes > 0) {
            //             return "{$hours} jam {$minutes} menit";
            //         } elseif ($hours > 0) {
            //             return "{$hours} jam";
            //         } else {
            //             return "{$minutes} menit";
            //         }
            //     })
            //     ->sortable(),

            TextColumn::make('job_duration')
                ->label('Durasi Pekerjaan')
                ->getStateUsing(function ($record) {
                    if (!$record->created_at || !$record->updated_at) {
                        return '-';
                    }

                    $start = \Carbon\Carbon::parse($record->created_at);
                    $end   = \Carbon\Carbon::parse($record->updated_at);

                    $diffInMinutes = $start->diffInMinutes($end);
                    $hours = floor($diffInMinutes / 60);
                    $minutes = $diffInMinutes % 60;

                    if ($hours > 0 && $minutes > 0) {
                        return "{$hours} jam {$minutes} menit";
                    } elseif ($hours > 0) {
                        return "{$hours} jam";
                    } else {
                        return "{$minutes} menit";
                    }
                })
                ->sortable(),

            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

             TextColumn::make('status')
                ->label('Status')
                ->sortable(query: function ($query, string $direction) {
                    return $query->orderBy('Timesheets.status', $direction);
                })
                ->searchable(query: function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('Timesheets.status', 'like', "%{$search}%")
                        ->orWhere('job_description', 'like', "%{$search}%")
                        ->orWhereHas('attendance.employee', function ($sub) use ($search) {
                            $sub->whereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                    });
                })
                ->formatStateUsing(fn($state) => match((int)$state) {
                    0 => 'On Progress',
                    1 => 'Pending',
                    2 => 'Done',
                    3 => 'Cancel',
                    default => 'Unknown',
                }),
        ])->defaultSort('created_at', 'desc')
        ->actions([
            Tables\Actions\EditAction::make(),
            // Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListTimesheets::route('/'),
            'create' => Pages\CreateTimesheet::route('/create'),
            'edit' => Pages\EditTimesheet::route('/{record}/edit'),
        ];
    }

    public static function formatAttendanceInfo($attendance): string
    {
        $attendanceDate = \Carbon\Carbon::parse($attendance->attendance_date)->translatedFormat('d M Y');
        $checkIn = $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '-';
        $checkOut = $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '-';
        $hours = number_format($attendance->working_hours, 2);

        return "Tanggal Attendance: {$attendanceDate} | Waktu: {$checkIn} → {$checkOut} ({$hours} jam)";
    }

    protected static function syncAttendanceInfo(callable $get, callable $set, $date): void
    {
        $employeeId = $get('employee_id');

        if (! $employeeId || ! $date) {
            $set('attendance_id', null);
            $set('attendance_info', '⚠️ Belum ada data attendance untuk tanggal ini.');
            return;
        }

        $dateFormatted = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('attendance_date', $dateFormatted)
            ->first();

        if ($attendance) {
            $set('attendance_id', $attendance->id);
            $set('attendance_info', self::formatAttendanceInfo($attendance));
        } else {
            $set('attendance_id', null);
            $set('attendance_info', '❌ Tidak ditemukan attendance pada tanggal ini.');
        }
    }
}
