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

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Hidden::make('attendance_id')
                // ->default(fn () => request()->get('attendance_id') 
                //     ?? auth()->user()->employee?->attendances()->latest()->first()?->id)
                ->required(),

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

            Forms\Components\Select::make('job_id')
                ->label('Job')
                ->relationship('job', 'title')
                ->searchable()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
