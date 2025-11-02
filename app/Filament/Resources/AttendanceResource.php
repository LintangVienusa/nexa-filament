<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Models\Employee;
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
use Carbon\Carbon;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Actions\Action;
use App\Filament\Resources\AttendanceResource\Widgets\AttendanceSummary;
use Illuminate\Database\Eloquent\Builder;


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
                Section::make('Bukti location')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Nama')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->required()
                            ->disabled()
                            ->dehydrated(true)
                            ->default(fn() => auth()->user()->employee?->employee_id),
                        TextInput::make('employee_nik')
                            ->label('NIK')
                            ->required()
                            ->default(fn($record) => $record?->employee_id
                                ?? auth()->user()->employee?->employee_id
                            )
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $component->state($record->employee_id);
                                } else {
                                    $component->state(auth()->user()->employee?->employee_id);
                                }
                            })
                            ->dehydrated(true)
                            ->readonly(),

                        DatePicker::make('attendance_date')
                            ->label('Tanggal Absensi')
                            ->default(Carbon::now('Asia/Jakarta'))
                            ->disabled()
                            ->required()
                            ->dehydrated(true),

                        TextInput::make('check_in_time_display')
                            ->label(fn($record) => $record ? 'Time Check Out' : 'Time Check In')
                            ->default(Carbon::now('Asia/Jakarta')->format('H:i'))
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // jika sedang edit (record ada)
                                if ($record) {
                                    $component->state(Carbon::now('Asia/Jakarta')->format('H:i'));
                                } else {
                                    $component->state(Carbon::now('Asia/Jakarta')->format('H:i'));
                                }
                            })
                            ->disabled()
                            ->required()
                            ->dehydrated(false),
                        Hidden::make('check_in_time')
                            ->default(fn($record) => $record
                                ? $record->check_in_time
                                : Carbon::now('Asia/Jakarta')->toDateTimeString()
                            )->afterStateHydrated(fn($record) => $record
                                ? $record->check_in_time
                                : Carbon::now('Asia/Jakarta')->toDateTimeString()
                            )
                             ->dehydrated(fn($record) => $record === null)
                            ->required(),
                        Hidden::make('check_out_time')
                            ->default(fn($record) => $record
                                ? Carbon::now('Asia/Jakarta')->toDateTimeString()
                                : Carbon::now('Asia/Jakarta')->toDateTimeString()
                            )->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->check_out_time) {
                                    $component->state($record->check_out_time); // gunakan value dari DB
                                } else {
                                    $component->state(Carbon::now('Asia/Jakarta')->toDateTimeString()); // default jika belum ada
                                }
                            })
                            ->dehydrated(true)
                            ->required()
                        ,

                    ])->columns(2),
                Section::make('Evidence')
                    ->schema([
                        // Section::make('location')
                        // ->schema([
                        //     ViewField::make('location_map_in')
                        // ->label('Lokasi Absensi')
                        // ->view('filament.partials.location-capture')
                        // ->visible(fn ($record) => $record && $record->check_in_latitude && $record->check_in_longitude),
                        ViewField::make('location_map')
                            ->view('filament.partials.location-map'),
                        // ]),s

                        Section::make('')
                            ->schema([
                                TextInput::make('check_in_latitude')
                                    ->label('Check In Latitude')
                                    ->required()
                                    ->dehydrated()
                                    ->default(fn($record) => $record ? $record->check_in_latitude : null)
                                    ->id('check_in_latitude'),
                                // ->visible(fn ($record) => !$record || !$record->check_in_latitude),

                                TextInput::make('check_in_longitude')
                                    ->label('Check In Longitude')
                                    ->required()
                                    ->dehydrated()
                                    ->default(fn($record) => $record ? $record->check_in_longitude : null)
                                    ->id('check_in_longitude'),
                                // ->visible(fn ($record) => !$record || !$record->check_in_latitude),

                                TextInput::make('check_out_latitude')
                                    ->label('Check Out Latitude')
                                    ->dehydrated()
                                    ->default(fn($record) => $record ? $record->check_out_latitude : null)
                                    ->id('check_out_latitude'),
                                // ->visible(fn ($record) => $record && $record->check_in_latitude && !$record->check_out_latitude),


                                TextInput::make('check_out_longitude')
                                    ->label('Check Out Longitude')
                                    ->dehydrated()
                                    ->default(fn($record) => $record ? $record->check_out_longitude : null)
                                    ->id('check_out_longitude'),
                                // ->visible(fn ($record) => $record && $record->check_in_latitude && !$record->check_out_latitude),
                            ])->columns(2),
                    ]),
                Section::make('Bukti Kehadiran')
                    ->schema([
                        Section::make('')
                            ->visible(fn($record) => !empty($record?->check_in_evidence) || !empty($record?->check_out_evidence))
                            ->schema([
                                ViewField::make('check_in_evidence')
                                    ->label('Check In Evidence')
                                    ->view('filament.partials.image-preview')
                                    ->visible(fn($record) => $record?->check_in_evidence), // safe navigation operator ?->

                                ViewField::make('check_out_evidence')
                                    ->label('Check Out Evidence')
                                    ->view('filament.partials.image-preview')
                                    ->visible(fn($record) => $record?->check_out_evidence), // safe navigation
                            ])->columns(2),
                        Hidden::make('check_in_evidence')
                            ->reactive()
                             ->dehydrated(fn($record) => $record === null)
                            ->required(),
                        Hidden::make('check_out_evidence')
                            ->reactive()->dehydrated()->dehydrated(),

                        ViewField::make('camera_capture')
                            ->view('filament.partials.camera-capture'),
                    ]),
                Hidden::make('created_by')
                    ->disabled()
                    ->default(auth()->user()->email ?? null),
            ]);
    }

    

    public static function table(Table $table): Table
    {
        $totalAttendance = Attendance::count();
        return $table
            ->columns([
                TextColumn::make('employee.employee_id')->label('Employee ID'),
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
                TextColumn::make('attendance_date')->date(),
                TextColumn::make('check_in_time')->dateTime(),
                TextColumn::make('check_out_time')->dateTime(),
                TextColumn::make('working_hours')
                    ->label('Working Hours')
                    ->getStateUsing(function ($record) {
                        if ($record->working_hours) {
                            // $hoursDecimal = $record->working_hours;
                            if($record->check_out_time){
                                $checkIn = $record->check_in_time ? Carbon::parse($record->check_in_time) : now();
                                $checkOut = $record->check_out_time ? Carbon::parse($record->check_out_time) : now();
                                $hoursDecimal = round($checkIn->floatDiffInHours($checkOut), 2); 
                            }else{
                                $checkIn = $record->check_in_time ? Carbon::parse($record->check_in_time) : now();
                                $hoursDecimal = round($checkIn->floatDiffInHours(now()), 2); 
                            } 
                        } else {
                            if($record->check_out_time){
                                $checkIn = $record->check_in_time ? Carbon::parse($record->check_in_time) : now();
                                $checkOut = $record->check_out_time ? Carbon::parse($record->check_out_time) : now();
                                $hoursDecimal = round($checkIn->floatDiffInHours($checkOut), 2); 
                            }else{
                                $checkIn = $record->check_in_time ? Carbon::parse($record->check_in_time) : now();
                                $hoursDecimal = round($checkIn->floatDiffInHours(now()), 2); 
                            }
                            
                        }

                        $hours = floor($hoursDecimal);
                        $minutes = round(($hoursDecimal - $hours) * 60);

                        return "{$hours} jam {$minutes} menit";
                    })
                    ->sortable(),
                    TextColumn::make('status')
                        ->label('Status')
                        ->formatStateUsing(fn($state) => match((int)$state) {
                            0 => 'On Time',
                            2 => 'Late',
                            3 => 'Alpha',
                            default => 'Unknown',
                        })
                        ->badge()
                        ->color(fn($state) => match((int)$state) {
                            0 => 'success',
                            2 => 'warning',
                            3 => 'danger',
                            default => 'secondary',
                        }),

                    TextColumn::make('notes')
                        ->label('Catatan')
                        ->wrap()
                        ->limit(50),
                    
            ])->defaultSort('attendance_date', 'desc')
            ->filters([
                Filter::make('today')
                    ->query(fn($query) => $query->whereDate('attendance_date', now()->toDateString())),
            ])
            
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager']))
                ->authorize(fn () => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager'])),
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
