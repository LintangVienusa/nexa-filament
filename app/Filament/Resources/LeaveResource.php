<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use App\Traits\HasOwnRecordPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasPermissions;
use App\Models\Employee;
use Filament\Forms\Components\FileUpload;


class LeaveResource extends Resource
{
    // use HasPermissions,  HasOwnRecordPolicy;

    protected static ?string $model = Leave::class;
    // protected static ?string $permissionPrefix = 'employees';
    // protected static string $ownerColumn = 'email';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Leaves';

    public static function getEloquentQuery(): Builder
    {
        $query = Leave::query()
                ->select('Leaves.*', 'Employees.first_name','Employees.middle_name','Employees.last_name', 'Employees.org_id')
                ->join('Employees', 'Leaves.employee_id', '=', 'Employees.employee_id');
        $user = auth()->user();
        $empId = $user->employee?->employee_id;
        $orgId = $user->employee?->org_id ?? 0;

        if ($user->isStaff() && $empId) {
            // Staff hanya bisa melihat data dirinya sendiri
            $query->where('Leaves.employee_id', $empId);
        } else {
            // Admin / Manager bisa melihat data dengan org_id yang sama
            $query->where('Employees.org_id', $orgId);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Info Karyawan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                                ->label('Nama')
                                ->options(Employee::all()->pluck('full_name', 'employee_id'))
                                ->searchable()
                                ->required()
                                ->default(fn ($record) => 
                                    $record?->employee_id 
                                    ?? auth()->user()->employee?->employee_id
                                )
                                ->disabled(fn ($state, $component, $record) => 
                                    $record !== null || auth()->user()->isStaff()
                                ) 
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $employee = \App\Models\Employee::find($state);
                                        $set('employee_nik', $employee?->employee_id);
                                    }
                                })->dehydrated(true),


                            Forms\Components\TextInput::make('employee_nik')
                                ->label('NIK')
                                ->required()
                                ->default(fn ($record) => 
                                    $record?->employee_id 
                                    ?? auth()->user()->employee?->employee_id
                                )
                                ->dehydrated(true)
                                ->disabled(fn ($state, $component, $record) => 
                                    $record !== null || auth()->user()->isStaff()
                                ),
                    ]),

                 Forms\Components\Section::make('Detail Cuti')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('leave_type')
                            ->label('Jenis Cuti')
                            ->options(function () {
                                $options = [
                                        1 => 'Cuti Tahunan',
                                        2 => 'Cuti Sakit',
                                        5 => 'Cuti Karena Alasan Penting',
                                        6 => 'Cuti Keagamaan',
                                ];

                                if (auth()->user()->employee?->gender === 'Perempuan') {
                                    $options[3] = 'Cuti Melahirkan / Keguguran';
                                    $options[4] = 'Cuti Haid';
                                }

                                if (auth()->user()->employee?->marital_status === 0) {
                                    $options[7] = 'Cuti Menikah';
                                }
                                return $options;
                            })

                            ->required()
                            ->afterStateUpdated(function  ($state, callable $set, callable $get){
                                $balance = match((int) $state) {
                                    1 => Leave::getAnnualLeaveBalance($get('employee_id')),
                                    3 => Leave::getMaternityLeaveBalance($get('employee_id')),
                                    7 => Leave::getMarriageLeaveBalance($get('employee_id')),
                                    default => 1,
                                };

                                // dump( $balance);
                                $set('annual_leave_balance', $balance);
                            })
                            ->searchable()
                            ->reactive()
                            ->preload(),


                        Forms\Components\FileUpload::make('leave_evidence')
                            ->label('Lampiran')
                            ->required(fn ($get) => $get('leave_type') === '2')
                            ->visible(fn ($get, $record) => $get('leave_type') === '2' || filled($record?->leave_evidence))
                            ->disk('public')
                            ->directory('leave-evidence')
                            ->downloadable()
                            ->openable()
                            ->previewable(true)
                            ->image()
                            ->reactive()
                            ->default(fn ($record) => $record?->leave_evidence ? $record->leave_evidence_url : null),
                        


                        Forms\Components\TextInput::make('annual_leave_balance')
                                ->label('Saldo Cuti')
                                ->disabled()
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if ($record) {
                                        $component->state(\App\Models\Leave::getAnnualLeaveBalance($record->employee_id));
                                    }
                                    
                                })
                                ->reactive()
                                ->visible(fn ($get) => in_array($get('leave_type'), [1,3,7])),
                   
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Dari Tgl')
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get) {
                                if ($get('start_date') && $get('end_date')) {
                                    $set('leave_duration', \Carbon\Carbon::parse($get('start_date'))
                                        ->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1);
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai Tgl')
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get) {
                                if ($get('start_date') && $get('end_date')) {
                                    $set('leave_duration', \Carbon\Carbon::parse($get('start_date'))
                                        ->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1);
                                }
                            }),

                        Forms\Components\TextInput::make('leave_duration')
                            ->label('Durasi (Hari)')
                            ->disabled()
                            ->dehydrated(true)
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get) {
                                if ($get('start_date') && $get('end_date')) {
                                    $days = \Carbon\Carbon::parse($get('start_date'))
                                        ->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1;

                                    $leaveType = $get('leave_type');
                                    if ($leaveType == 7 && $days > 7) {
                                        $days = 7; 
                                    }
                                    if ($leaveType == 3 && $days > 90) {
                                        $days = 90; 
                                    }

                                    $set('leave_duration', $days);
                                }
                            }),
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->columnSpanFull(),
                    
                    ]),
                Forms\Components\Select::make('status')
                    ->options(function () {
                        $user = auth()->user();

                        if ($user->isManager()) {
                            return [
                                1 => 'Ditunda',
                                2 => 'Disetujui',
                                3 => 'Ditolak',
                            ];
                        }

                        if ($user->isStaff()) {
                            return [
                                0 => 'Kirim',
                            ];
                        }

                        return [];
                    })
                    ->default(fn () => auth()->user()->isStaff() ? 0 : null)
                    ->hidden(fn () => auth()->user()->isStaff())
                    ->required()
                    ->reactive(),

                Forms\Components\Textarea::make('note_rejected')
                    ->label('Keterangan')
                    ->visible(fn (callable $get) => $get('status') == 3)
                    ->required(fn (callable $get) => $get('status') == 3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
             
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Jenis Cuti')
                    ->formatStateUsing(function ($state, $record) {
                            $options = [
                                1 => 'Cuti Tahunan',
                                2 => 'Cuti Sakit',
                                3 => 'Cuti Melahirkan / Keguguran',
                                4 => 'Cuti Haid',
                                5 => 'Cuti Karena Alasan Penting',
                                6 => 'Cuti Keagamaan',
                                7 => 'Cuti Menikah',
                            ];
                            return $options[$state] ?? $state;
                        })
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Dari Tgl')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Sampai Tgl')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leave_duration')
                    ->label('Durasi')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => 'Kirim',
                        1 => 'Ditunda',
                        2 => 'Disetujui',
                        3 => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn ($state): string => match ($state) {
                        0 => 'warning',
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_by')
                    ->label('Disetujui oleh')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('leave_evidence')
                //     ->searchable(),
                    Tables\Columns\ImageColumn::make('leave_evidence')
                    ->label('Lampiran')
                    ->disk('public') 
                    ->height(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
               Actions\Action::make('approved')
                        ->label('Disetujui')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => (int)$record->status === 0  && ! auth()->user()->isStaff()) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $type = $record->leave_type;
                            $record->update(['status' => 2]);
                            Notification::make()
                                ->title( $type .' Disetujui')
                                ->success()
                                ->send();
                                return $record->fresh();
                        }),
                Actions\Action::make('reject')
                        ->label('Ditolak')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn ($record) => (int)$record->status === 0  && ! auth()->user()->isStaff()) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                              $record->update(['status' => 3]);
                            Notification::make()
                                ->title( $type .' Ditolak')
                                ->success()
                                ->send();
                                return $record->fresh();
                                
                                
                        }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => (int)$record->status != 2) ,
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
