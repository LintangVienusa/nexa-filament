<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Organization;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Traits\HasPermissions;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\Hidden;
use App\Traits\HasNavigationPolicy;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;


class EmployeeResource extends Resource
{

    use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;

    protected static ?string $model = Employee::class;
    protected static ?string $permissionPrefix = 'employees';
    protected static string $ownerColumn = 'email';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Employees';

    public static function canCreate(): bool
    {
        return auth()->user()->setConnection('mysql')->hasAnyRole(['admin', 'manager', 'superadmin', 'Supervisor', 'Admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Personal')
                    ->schema([
                        FileUpload::make('file_photo')
                            ->label('Photo')
                            ->directory('employee')
                            ->maxSize(1024)
                            ->preserveFilenames(),

                        TextInput::make('employee_id')
                            ->label('Nomor Induk Karyawan(NIK)')
                            ->numeric()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('first_name')
                            ->label('Nama Depan')
                            ->required()
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn($state) => strtoupper($state))
                            ->reactive()
                            ->maxLength(50),

                        TextInput::make('middle_name')
                            ->label('Nama Tengah')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn($state) => strtoupper($state))
                            ->reactive()
                            ->maxLength(50),

                        TextInput::make('last_name')
                            ->label('Nama Belakang')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn($state) => strtoupper($state))
                            ->required()
                            ->reactive()
                            ->maxLength(50),

                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->required(),

                        DatePicker::make('date_of_birth')
                            ->label('Tanggal Lahir'),
                    ])->columns(2),

                Section::make('Detail Karyawan')
                    ->schema([
                        DatePicker::make('date_of_joining')
                            ->label('Tanggal Masuk')
                            ->default(now()),

                        Select::make('employee_type')
                            ->label('Tipe')
                            ->options(function () {
                                $options = [
                                    'organik' => 'organik',
                                    'mitra' => 'mitra'
                                ];
                                return $options;
                            })
                            ->required(),

                        Select::make('job_title')
                            ->label('Jabatan')
                            ->options(function () {
                                $options = [
                                    'Staff' => 'Staff',
                                    'SPV' => 'SPV',
                                    'Manager' => 'Manager',
                                    'VP' => 'VP',
                                ];

                                if (auth()->user()->setConnection('mysql')->hasRole('superadmin')) {
                                    $options['CTO'] = 'CTO';
                                    $options['CEO'] = 'CEO';
                                }

                                return $options;
                            })
                            ->required(),

                        Select::make('divisi_name')
                            ->label('Department')
                            ->options(Organization::query()
                                ->select('id', 'divisi_name')
                                ->distinct('divisi_name')
                                ->pluck('divisi_name', 'divisi_name'))
                            ->reactive()
                            ->required()
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record && $record->org_id) {
                                    $organization = Organization::find($record->org_id);
                                    if ($organization) {
                                        $set('divisi_name', $organization->divisi_name);
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('unit_id', null);
                            }),

                        Select::make('unit_id')
                            ->label('Unit')
                            ->options(function (callable $get) {
                                $departmentName = $get('divisi_name');
                                if (!$departmentName) return [];
                                return Organization::where('divisi_name', $departmentName)
                                    ->pluck('unit_name', 'id');
                            })
                            ->required()
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record && $record->org_id) {
                                    $organization = Organization::find($record->org_id);
                                    if ($organization) {
                                        $set('unit_id', $organization->id);
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {

                                $set('org_id', $state);
                                $set('org_id_display', $state);
                            })
                            ->reactive(),

                        Hidden::make('org_id')
                            ->reactive(),

                        TextInput::make('basic_salary')
                            ->label('Gaji Pokok')
                            ->prefix('Rp')
                            ->reactive()
                            ->default(0)
                            ->formatStateUsing(fn($state) => number_format((int)$state, 0, ',', '.'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                $number = preg_replace('/[^0-9]/', '', $state);
                                $set('basic_salary', $number === '' ? 0 : number_format((int)$number, 0, ',', '.'));
                            })
                            ->hidden(function ($record) {
                                $user = auth()->user();
                                $organizationName = $user->employee?->organization?->unit_name;
                                if (filled($record) && $organizationName !== 'HR') {
                                    return true;
                                }

                                return false;
                            })
                            ->dehydrateStateUsing(fn($state) => (string)preg_replace('/[^0-9]/', '', $state))
                            ->required(),
                    ])->columns(2)->disabled(function ($record) {
                        $user = auth()->user();
                        $organizationName = $user->employee?->organization?->unit_name;
                        if (filled($record) && $organizationName !== 'HR') {
                            return true;
                        }

                        return false;
                    }),

                Section::make('Kontak dan Informasi Detail')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),

                        TextInput::make('mobile_no')
                            ->label('Nomor Handphone')
                            ->tel()
                            ->numeric()
                            ->maxLength(16)
                            ->helperText('Masukan 08xxxxxxxxxx')
                            ->required(),

                        TextInput::make('ktp_no')
                            ->label('No. KTP')
                            ->numeric()
                            ->maxLength(16)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (strlen($state) > 16) {
                                    $set('ktp_no', substr($state, 0, 16));
                                }
                            })
                            ->required()
                            ->helperText('Masukkan 16 digit KTP')
                            ->rule('digits:16'),

                        TextInput::make('bpjs_kes_no')
                            ->label('No. BPJS Kesehatan')
                            ->numeric()
                            ->maxLength(20)
                            ->required(),

                        TextInput::make('bpjs_tk_no')
                            ->label('No. BPJS Ketenagakerjaan')
                            ->numeric()
                            ->maxLength(20)
                            ->required(),

                        TextInput::make('npwp_no')
                            ->label('NPWP No.')
                            ->numeric()
                            ->maxLength(20)
                            ->required(),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->required(),
                    ])->columns(2),

                Section::make('Info Tambahan')
                    ->schema([
                        Select::make('religion')
                            ->label('Agama')
                            ->options([
                                'Islam' => 'Islam',
                                'Kristen' => 'Kristen',
                                'Katolik' => 'Katolik',
                                'Hindu' => 'Hindu',
                                'Buddha' => 'Buddha',
                                'Konghucu' => 'Konghucu',
                            ]),

                        Select::make('marital_status')
                            ->label('Status Pernikahan')
                            ->options([
                                0 => "Belum Menikah",
                                1 => "Menikah"
                            ])
                            ->reactive()
                            ->required(),

                        TextInput::make('number_of_children')
                            ->label('Jumlah Anak')
                            ->numeric()
                            ->default(0)
                            ->visible(fn(callable $get) => $get('marital_status') == 1)
                    ])->columns(2),

                Section::make('Info Rekening')
                    ->schema([
                        TextInput::make('bank_account_name')
                            ->label('Bank')
                            ->maxLength(100)
                            ->required(),

                        TextInput::make('bank_account_no')
                            ->label('No. Rekening')
                            ->numeric()
                            ->maxLength(50)
                            ->required(),

                        TextInput::make('name_in_bank_account')
                            ->label('Nama di Rekening')
                            ->maxLength(100)
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_photo')->label('Photo'),
                TextColumn::make('employee_id')->searchable(),
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

                TextColumn::make('employee_type')->label('Tipe')->searchable()->sortable(),
                TextColumn::make('organization.divisi_name')->label('Divisi')->searchable()->sortable(),
                TextColumn::make('organization.unit_name')->label('Unit')->searchable()->sortable(),
                TextColumn::make('job_title')->label('Jabatan')->searchable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('divisi_name')
                    ->label('Divisi')
                    ->options(
                        Organization::query()
                            ->whereNotNull('divisi_name')
                            ->distinct()
                            ->pluck('divisi_name', 'divisi_name')
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('organization', fn($q) => $q->where('divisi_name', $data['value']));
                        }
                    }),

                SelectFilter::make('unit_name')
                    ->label('Unit')
                    ->options(
                        Organization::query()
                            ->whereNotNull('unit_name')
                            ->distinct()
                            ->pluck('unit_name', 'unit_name')
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('organization', fn($q) => $q->where('unit_name', $data['value']));
                        }
                    }),

                SelectFilter::make('job_title')
                    ->label('Jabatan')
                    ->options(
                        Employee::query()
                            ->whereNotNull('job_title')
                            ->distinct()
                            ->pluck('job_title', 'job_title')
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->where('job_title', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager']))
                        ->authorize(fn() => auth()->user()?->hasAnyRole(['superadmin', 'admin', 'manager'])),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
