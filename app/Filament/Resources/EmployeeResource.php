<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
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
use Spatie\Permission\Traits\HasPermissions;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;


class EmployeeResource extends Resource
{
    use HasPermissions, HasOwnRecordPolicy;

    protected static ?string $model = Employee::class;
    protected static ?string $permissionPrefix = 'employees';
    protected static string $ownerColumn = 'email';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Employees';

    public static function canCreate(): bool
    {
        // Hanya admin dan manager yang boleh membuat Employee baru
        return auth()->user()->hasAnyRole(['admin', 'manager']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Personal')
                    ->schema([

                        FileUpload::make('file_photo')
                            ->label('Photo')
                            ->image()
                            ->directory('employee-photos')
                            ->acceptedFileTypes(['image/jpeg']) 
                            ->visibility('public')
                            ->imageResizeTargetWidth(500)
                            ->imageResizeTargetHeight(900)
                            ->required()
                            ->maxSize(1024),

                        TextInput::make('employee_id')
                            ->label('Nomor Induk Karyawan(NIK)')
                            ->numeric()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('first_name')
                            ->label('Nama Depan')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('middle_name')
                            ->label('Nama Tengah')
                            ->maxLength(50),

                        TextInput::make('last_name')
                            ->label('Nama Belakang')
                            ->required()
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

                        TextInput::make('job_title')
                            ->label('Jabatan')
                            ->maxLength(100),

                        Select::make('org_id')
                            ->label('Divisi')
                            ->relationship('organization', 'divisi_name')
                            ->searchable()
                            ->required(),

                        TextInput::make('basic_salary')
                            ->label('Gaji Pokok')
                            ->prefix('Rp')
                            ->reactive()
                            ->default(0)
                            ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                $number = preg_replace('/[^0-9]/', '', $state);
                                $set('basic_salary', $number === '' ? 0 : number_format((int) $number, 0, ',', '.'));
                            })
                            ->dehydrateStateUsing(fn($state) => (string) preg_replace('/[^0-9]/', '', $state))
                            ->required(),
                    ])->columns(2),

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
                            ->required(),

                        TextInput::make('ktp_no')
                            ->label('No. KTP')
                            ->required()
                            ->numeric()
                            ->maxLength(20),

                        TextInput::make('bpjs_kes_no')
                            ->label('No. BPJS Kesehatan')
                            ->numeric()
                            ->maxLength(20),

                        TextInput::make('bpjs_tk_no')
                            ->label('No. BPJS Ketenagakerjaan')
                            ->numeric()
                            ->maxLength(20),

                        TextInput::make('npwp_no')
                            ->label('NPWP No.')
                            ->numeric()
                            ->maxLength(20),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3),
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
                            ->reactive(),

                        TextInput::make('number_of_children')
                            ->label('Jumlah Anak')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (callable $get) => $get('marital_status') == 1)
                    ])->columns(2),

                Section::make('Info Rekening')
                    ->schema([
                        TextInput::make('bank_account_name')
                            ->label('Bank')
                            ->maxLength(100),

                        TextInput::make('bank_account_no')
                            ->label('No. Rekening')
                            ->numeric()
                            ->maxLength(50),

                        TextInput::make('name_in_bank_account')
                            ->label('Nama di Rekening')
                            ->maxLength(100),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_photo')->label('Photo'),
                Tables\Columns\TextColumn::make('employee_id'),
                Tables\Columns\TextColumn::make('first_name'),
                Tables\Columns\TextColumn::make('last_name'),
                Tables\Columns\TextColumn::make('organization.divisi_name')->label('Organization'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
