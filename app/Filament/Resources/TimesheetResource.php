<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimesheetResource\Pages;
use App\Filament\Resources\TimesheetResource\RelationManagers\JobsRelationManager;
use App\Models\Attendance;
use App\Models\Timesheet;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms;
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
        return $form->schema([
            Forms\Components\Hidden::make('employee_id')
                ->default(fn () => auth()->user()->employee?->employee_id),

            Forms\Components\DatePicker::make('timesheet_date')
                ->label('Timesheet Date')
                ->required()
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

            Forms\Components\Textarea::make('job_description')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('job_duration')
                ->numeric()
                ->required()
                ->suffix(' jam'),

            Forms\Components\Hidden::make('created_by')
                ->default(fn () => auth()->user()->employee?->employee_id),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('attendance.attendance_date')
                ->label('Attendance Date')
                ->sortable(),

            Tables\Columns\TextColumn::make('job_description')
                ->label('Job Description')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('job_duration')
                ->label('Duration (Hours)')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
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
}
