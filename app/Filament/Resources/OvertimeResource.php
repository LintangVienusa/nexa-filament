<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeResource\Pages;
use App\Filament\Resources\OvertimeResource\RelationManagers;
use App\Models\Overtime;
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
use App\Models\Attendance;

class OvertimeResource extends Resource
{
    use HasOwnRecordPolicy;

    protected static ?string $model = Overtime::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $permissionPrefix = 'employees';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Overtimes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'full_name')
                    ->searchable()
                    ->required()
                    ->default(fn() => auth()->user()->employee?->employee_id),

                Forms\Components\DatePicker::make('overtime_date')
                    ->label('Overtime Date')
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

                Forms\Components\TimePicker::make('start_time')
                    ->label('Start Time')
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $end = $get('end_time');
                        if ($state && $end) {
                            $start = Carbon::parse($state);
                            $e = Carbon::parse($end);
                            if ($e->lessThan($start)) $e->addDay();
                            $minutes = $start->diffInMinutes($e);
                            $set('working_hours', round($minutes / 60, 2));
                        }
                    }),

                Forms\Components\TimePicker::make('end_time')
                    ->label('End Time')
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $start = $get('start_time');
                        if ($start && $state) {
                            $s = Carbon::parse($start);
                            $end = Carbon::parse($state);
                            if ($end->lessThan($s)) $end->addDay();
                            $minutes = $s->diffInMinutes($end);
                            $set('working_hours', round($minutes / 60, 2));
                        }
                    }),

                Forms\Components\TextInput::make('working_hours')
                    ->label('Working Hours')
                    ->numeric()
                    ->disabled()
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Select::make('job_id')
                    ->label('Job')
                    ->relationship('job', 'job_description')
                    ->preload()
                    ->searchable(),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn() => auth()->id())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('job.job_description')->label('Job')->sortable(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('working_hours')->label('Hours'),
                Tables\Columns\TextColumn::make('start_time')->time(),
                Tables\Columns\TextColumn::make('end_time')->time(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
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
