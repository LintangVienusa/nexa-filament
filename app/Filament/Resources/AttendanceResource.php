<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Spatie\Permission\Traits\HasPermissions;

class AttendanceResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy;
    protected static ?string $model = Attendance::class;
    protected static ?string $permissionPrefix = 'employees';
    protected static string $ownerColumn = 'email';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Attendances';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->Label('NIK / ID Karyawan')
                    ->relationship('employee', 'employee_id')
                    ->searchable()
                    ->required(),
                DatePicker::make('attendance_date')
                    ->Label('Tanggal Attendance')
                    ->default(now())
                    ->required(),
                DateTimePicker::make('check_in_time')
                    ->Label('Waktu Check In')
                    ->default(now())
                    ->required(),
                DateTimePicker::make('check_out_time')
                    ->Label('Waktu Check Out')
                    ->default(now()),
                TextInput::make('working_hours')
                    ->label('Waktu Kerja (Jam)')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($record) => $record?->working_hours),
                FileUpload::make('check_in_evidence')
                    ->Label('Evidence Check In')
                    ->directory('attendances/checkin')
                    ->maxSize(1024)
                    ->preserveFilenames(),
                TextInput::make('check_in_latitude')->numeric(),
                TextInput::make('check_in_longitude')->numeric(),
                TextInput::make('check_out_latitude')->numeric(),
                TextInput::make('check_out_longitude')->numeric(),
                TextInput::make('created_by')
                    ->disabled()
                    ->default(auth()->user()->email ?? null),
                TextInput::make('updated_by')
                    ->disabled()
                    ->default(auth()->user()->email ?? null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.employee_id')->label('Employee ID'),
                TextColumn::make('employee.full_name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attendance_date')->date(),
                TextColumn::make('check_in_time')->dateTime(),
                TextColumn::make('check_out_time')->dateTime(),
                TextColumn::make('working_hours')
                    ->getStateUsing(fn ($record) => $record->working_hours)
                    ->numeric(2)
                    ->suffix(' hrs')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('attendance_date', now()->toDateString())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
