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
use Filament\Notifications\Notification;
use App\Services\HariKerjaService;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;



class LeaveResource extends Resource
{
    use HasPermissions,  HasOwnRecordPolicy;

    protected static ?string $model = Leave::class;
    protected static ?string $permissionPrefix = 'employees';
    protected static string $ownerColumn = 'email';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Leaves';


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
                                ->formatStateUsing(function ($state, $record) {
                                    return $record?->employee_id ?? $state ?? auth()->user()->employee?->employee_id;
                                })
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
                                    5 => Leave::getImportantReasonLeaveBalance($get('employee_id')),
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
                                ->visible(fn ($get) => in_array($get('leave_type'), [1,3,5,7])),
                   
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
                            ->afterStateUpdated(function ($state,$set, $get) {
                                if ($get('start_date') && $get('end_date')) {
                                     $startDate = $get('start_date');
                                     $endDate = $get('end_date');
                                     $hariKerjaService = app(HariKerjaService::class);
                                     $hariKerjaData = $hariKerjaService->hitungHariKerja($state, $startDate, $endDate);
                                     $jml = $hariKerjaData['jumlah_hari_kerja'] ?? 0;
                                    $set('leave_duration', $jml);
                                }
                            }),

                        Forms\Components\TextInput::make('leave_duration')
                            ->label('Durasi (Hari)')
                            ->disabled()
                            ->dehydrated(true)
                            ->reactive()
                            ->dehydrateStateUsing(function ($state, $set, $get) {
                                if ($get('start_date') && $get('end_date')) {
                                     $startDate = $get('start_date');
                                     $endDate = $get('end_date');
                                     $hariKerjaService = app(HariKerjaService::class);
                                     $hariKerjaData = $hariKerjaService->hitungHariKerja($state, $startDate, $endDate);
                                     $jml = $hariKerjaData['jumlah_hari_kerja'] ?? 0;
                                    $set('leave_duration', $jml);
                                    return $hariKerjaData['jumlah_hari_kerja'] ?? 0;
                                }else{
                                    return 0;
                                }
                            }),
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->columnSpanFull(),
                    
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(function ($record) {
                               $status = $record->status ?? null;

                                $levels = [
                                    2 => ['Approved Manager'],
                                    3 => ['Reject Manager'],
                                    4 => ['Approved Manager','Approved VP'],
                                    5 => ['Reject Manager','Reject VP'],
                                    6 => ['Approved Manager','Approved VP','Approved CEO/CTO'],
                                    7 => ['Reject Manager','Reject VP','Reject CEO/CTO'],
                                ];

                                $approveStatuses = [2,4,6];
                                $rejectStatuses = [3,5,7];

                                $approvers = $levels[$status] ?? [];
                                $symbol = '';

                                if (in_array($status, $approveStatuses)) {
                                    $symbol = '✔️';
                                } elseif (in_array($status, $rejectStatuses)) {
                                    $symbol = '❌';
                                }

                                if (empty($approvers)) {
                                    return $status === 0 ? 'Draft' : ($status === 1 ? 'Pending' : 'Unknown');
                                }

                                $lines = array_map(fn($level) => "$symbol $level", $approvers);

                                $html = implode('<br>', $lines);

                                return new HtmlString($html);
                            })
                            ->extraAttributes(function ($record) {
                                $approveStatus = [2, 4, 6];
                                $rejectStatus = [3, 5, 7];

                                $status = $record->status ?? null;

                                if (in_array($status, $approveStatus)) {
                                    return ['style' => 'color: green; font-weight: bold;'];
                                } elseif (in_array($status, $rejectStatus)) {
                                    return ['style' => 'color: red; font-weight: bold;'];
                                }

                                return [];
                            }),

                        Forms\Components\Textarea::make('note_rejected')
                            ->label('Keterangan')
                            ->visible(fn (callable $get) => $get('status') == 3) 
                            ->required(fn (callable $get) => $get('status') == 3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->hidden(fn (string $operation) => $operation === 'create'),
            ])
            ->disabled(fn ($livewire, $record) => $livewire instanceof \Filament\Resources\Pages\EditRecord && $record?->status > 1);
    }

    public static function table(Table $table): Table
    {
        return $table
             
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama')
                    ->sortable()
                     ->searchable(query: function ($query, $search) {
                        $query->whereHas('employee', function ($q) use ($search) {
                            $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                    }),
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
                    ->sortable()->searchable(),
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
                        0 => 'Draft',
                        1 => 'Pending',
                        2 => 'Approve Manager',
                        3 => 'Reject Manager',
                        4 => 'Approve VP',
                        5 => 'Reject VP',
                        6 => 'Approve CEO/CTO',
                        7 => 'Reject CEO/CTO',
                        default => $state,
                    })
                    ->color(fn ($state): string => match ($state) {
                        0 => 'warning',
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        4 => 'success',
                        5 => 'danger',
                        6 => 'success',
                        7 => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),
                 Tables\Columns\ImageColumn::make('leave_evidence')
                    ->label('Lampiran')
                    ->disk('public') 
                    ->height(50),
                
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Dibuat oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_by')
                    ->label('Disetujui oleh')
                    ->getStateUsing(function ($record) {
                        return match ($record->status) {
                            2 => 'Manager',
                            4 => 'Manager, VP',
                            6 => 'Manager, VP, CEO/CTO',
                            // 3 => 'Reject Manager',
                            // 5 => 'Reject VP',
                            // 7 => 'Reject CEO/CTO',
                            default => '-',
                        };
                    })
                    ->searchable(),
                // Tables\Columns\TextColumn::make('leave_evidence')
                //     ->searchable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Approved at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type')
                    ->label('Jenis Cuti')
                    ->options([
                        1 => 'Cuti Tahunan',
                        2 => 'Cuti Sakit',
                        3 => 'Cuti Melahirkan / Keguguran',
                        4 => 'Cuti Haid',
                        5 => 'Cuti Karena Alasan Penting',
                        6 => 'Cuti Keagamaan',
                        7 => 'Cuti Menikah',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        0 => 'Draft',
                        1 => 'Pending',
                        2 => 'Approve Manager',
                        3 => 'Reject Manager',
                        4 => 'Approve VP',
                        5 => 'Reject VP',
                        6 => 'Approve CEO/CTO',
                        7 => 'Reject CEO/CTO',
                    ]),
            ])
            ->actions([
            
            //    Actions\Action::make('approved')
            //             ->label('Approve')
            //             ->color('success')
            //             ->icon('heroicon-o-check')
            //             ->visible(fn ($record) => (int)$record->status === 0  && ! auth()->user()->isStaff()) 
            //             ->requiresConfirmation()
            //             ->action(function ($record) {
            //                 $type = $record->leave_type;
            //                 $record->update(['status' => 2]);
            //                 activity('Leaves-action')
            //                     ->causedBy(auth()->user())
            //                     ->withProperties([
            //                         'ip'    => request()->ip(),
            //                         'menu'  => 'Leaves',
            //                         'email' => auth()->user()?->email,
            //                         'record_id' => $record->id,
            //                         'Leaves' => $record->id,
            //                         'action' => 'Approve',
            //                     ])
            //                     ->tap(function ($activity) {
            //                             $activity->email = auth()->user()?->email;
            //                             $activity->menu = 'Leaves';
            //                         })
            //                     ->log('Leaves disetujui');

            //                 Notification::make()
            //                     ->title( $type .' Approve')
            //                     ->success()
            //                     ->send();
            //                     return $record->fresh();
            //             }),
                Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => match(auth()->user()->employee?->job_title) {
                            'Manager' => $record->approval_1 == 0,
                            'VP' => $record->approval_2 == 0 && $record->approval_1 == 1,
                            'CEO', 'CTO' => $record->approval_3 == 0 && $record->approval_2 == 1,
                            default => false,
                        })
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $job = auth()->user()->employee?->job_title;
                            $userId = auth()->user()->email;

                            if ($job === 'Manager' && $record->approval_1 == 0) {
                                $record->update([
                                    'approval_1' => 1,
                                    'status' => 2,
                                    'approved_1_at' => now(),
                                    'approval_1_by' => $userId,
                                ]);
                            } elseif ($job === 'VP' && $record->approval_2 == 0 && $record->approval_1 == 1) {
                                $record->update([
                                    'approval_2' => 1,
                                    'status' => 4,
                                    'approved_2_at' => now(),
                                    'approval_2_by' => $userId,
                                ]);
                            } elseif (in_array($job, ['CEO','CTO']) && $record->approval_3 == 0 && $record->approval_2 == 1) {
                                $record->update([
                                    'approval_3' => 1,
                                    'status' => 6,
                                    'approved_3_at' => now(),
                                    'approval_3_by' => $userId,
                                ]);
                            }

                            Notification::make()->title('Leave Approved')->success()->send();

                            return $record->fresh();
                        }),
                Actions\Action::make('reject')
                        ->label('Reject')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn ($record) => (int)$record->status === 0  && ! auth()->user()->isStaff()) 
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            
                            $type = $record->leave_type;
                              $record->update(['status' => 3]);

                              activity('Leaves-action')
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'ip'    => request()->ip(),
                                    'menu'  => 'Leaves',
                                    'email' => auth()->user()?->email,
                                    'record_id' => $record->id,
                                    'Leaves' => $record->id,
                                    'action' => 'Approve',
                                ])
                                ->tap(function ($activity) {
                                        $activity->email = auth()->user()?->email;
                                        $activity->menu = 'Leaves';
                                    })
                                ->log('Leaves tidak disetujui');
                            Notification::make()
                                ->title( $type .' Reject')
                                ->success()
                                ->send();
                                return $record->fresh();
                                
                                
                        }),
                Actions\ViewAction::make(),
                Actions\EditAction::make()->visible(fn ($record) => $record->status <= 1),
                Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => 
                            $record->created_by === auth()->user()->email
                            && $record->approval_1 == 0              
                            && $record->approval_2 == 0             
                            && $record->approval_3 == 0              
                        ),
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
