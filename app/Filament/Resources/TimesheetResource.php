<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimesheetResource\Pages;
use App\Filament\Resources\TimesheetResource\RelationManagers\JobsRelationManager;
use App\Models\Attendance;
use App\Models\Timesheet;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Traits\HasPermissions;

class TimesheetResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy;
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
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    static::syncAttendanceInfo($get, $set, $state);
                }),

            TextInput::make('attendance_id')
                ->label('ID Attendance')
                ->disabled()
                ->dehydrated(true),

            Section::make('Informasi Kehadiran')
                ->collapsible()
                ->schema([
                    ViewField::make('attendance_info')
                        ->label('Attendance Info')
                        ->view('filament.forms.component.attendance-info')
                        ->dehydrated(false)
                        ->reactive(false),
                ]),

            Textarea::make('job_description')
                ->required()
                ->columnSpanFull(),

            TextInput::make('job_duration')
                ->numeric()
                ->required()
                ->suffix(' jam'),

            Hidden::make('created_by')
                ->default(fn () => auth()->user()->email ?? null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('attendance_id')
                ->label('Attendance')
                ->sortable()
                ->formatStateUsing(fn ($state) => "View #{$state}")
                ->url(fn ($record) => $record->attendance_id
                    ? AttendanceResource::getUrl('edit', ['record' => $record->attendance_id])
                    : null)
                ->openUrlInNewTab(),

            Tables\Columns\TextColumn::make('attendance.attendance_date')
                ->label('Tanggal Attendance')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('job_description')
                ->label('Detail Pekerjaan')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('job_duration')
                ->label('Durasi (Jam)')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
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

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date)
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
