<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeResource\Pages;
use App\Filament\Resources\OvertimeResource\RelationManagers;
use App\Models\Overtime;
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

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Overtimes';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika user login adalah staff → hanya lihat cuti miliknya
        if (Auth::check() && Auth::user()->isStaff()) {
            $query->where('employee_id', Auth::user()->employee?->employee_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Hidden::make('attendance_id')
                ->default(fn () => \App\Models\Overtime::getLatestAttendanceId())
                ->required(),
            Forms\Components\Hidden::make('employee_id')
                    ->default(fn () => auth()->user()->employee?->employee_id)
                    ->visible(fn () => auth()->user()->isStaff()),

            Forms\Components\TimePicker::make('start_time')
                ->label('Start Time')
                ->reactive()
                ->required()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    // hitung jika end_time ada
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
                ->relationship('job', 'job_name')
                ->preload()
                ->searchable(),
            Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->user()->employee?->employee_id)
                    ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Employee Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('job.job_name')
                    ->label('Job')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->sortable(),
                Tables\Columns\TextColumn::make('working_hours')
                    ->label('Working Hours')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListOvertimes::route('/'),
            'create' => Pages\CreateOvertime::route('/create'),
            'edit' => Pages\EditOvertime::route('/{record}/edit'),
        ];
    }
}
