<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;


class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Leaves';

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
                Forms\Components\Hidden::make('employee_id')
                    ->default(fn () => auth()->user()->employee?->employee_id)
                    ->visible(fn () => auth()->user()->isStaff()),
                Forms\Components\Select::make('leave_type')
                    ->label('Leave Type')
                    ->options(function () {
                         $options = [
                                1 => 'Annual Leave',
                                2 => 'Sick Leave',
                                5 => 'Leave for Important Reasons',
                                6 => 'Religious Leave',
                         ];

                         if (auth()->user()->employee?->gender === 'Female') {
                            $options[3] = 'Maternity / Miscarriage Leave';
                            $options[4] = 'Menstrual Leave';
                        }

                        if (auth()->user()->employee?->marital_status === 0) {
                            $options[7] = 'Marriage Leave';
                        }
                        return $options;
                    })

                    ->required()
                    ->searchable()
                    ->reactive()
                    ->preload(),


                Forms\Components\FileUpload::make('leave_evidence')
                    ->label('Evidence')
                    ->required(fn ($get) => $get('leave_type') === '2') // wajib jika leave_type Sakit
                    ->visible(fn ($get) => $get('leave_type') === '2') // opsional, supaya field muncul hanya kalau Sakit
                    ->disk('public') // sesuaikan dengan disk
                    ->directory('leave-evidence') // folder penyimpanan
                    ->reactive(),

                Forms\Components\TextInput::make('annual_leave_balance')
                        ->label('Remaining Annual Leave')
                        ->default(fn () => Leave::getAnnualLeaveBalance(auth()->user()->employee?->employee_id))
                        ->disabled() // tidak bisa di-edit
                        ->dehydrated(false)
                        ->visible(fn ($get) => $get('leave_type') == 1),
                Forms\Components\DatePicker::make('start_date')
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        if ($get('start_date') && $get('end_date')) {
                            $set('leave_duration', \Carbon\Carbon::parse($get('start_date'))
                                ->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1);
                        }
                    }),

                Forms\Components\DatePicker::make('end_date')
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        if ($get('start_date') && $get('end_date')) {
                            $set('leave_duration', \Carbon\Carbon::parse($get('start_date'))
                                ->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1);
                        }
                    }),

                Forms\Components\TextInput::make('leave_duration')
                    ->label('Duration (days)')
                    ->disabled()
                    ->dehydrated(true) // biar tetap tersimpan ke DB
                    ->afterStateUpdated(function ($set, $get) {
                        if ($get('start_date') && $get('end_date')) {
                            $days = \Carbon\Carbon::parse($get('start_date'))
                                ->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1;
                            $set('leave_duration', $days);
                        }
                    })
                    ->reactive(),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(function () {
                        $user = auth()->user();

                        if ($user->isManager()) {
                            return [
                                1 => 'Pending',
                                2 => 'Approved',
                                3 => 'Rejected',
                            ];
                        }

                        if ($user->isStaff()) {
                            return [
                                0 => 'Submit',
                            ];
                        }

                        return [];
                    })
                    ->default(fn () => auth()->user()->isStaff() ? 0 : null)
                    ->hidden(fn () => auth()->user()->isStaff()) // sembunyikan input untuk staff
                    ->required()
                    ->reactive(),

                Forms\Components\Textarea::make('note_rejected')
                    ->label('Rejection Note')
                    ->visible(fn (callable $get) => $get('status') == 3)
                    ->required(fn (callable $get) => $get('status') == 3)
                    ->columnSpanFull(),
                // Forms\Components\TextInput::make('leave_evidence')
                //     ->maxLength(50),
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
                Tables\Columns\TextColumn::make('leave_type')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leave_duration')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge() // tampilkan sebagai badge
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => 'Submit',
                        1 => 'Pending',
                        2 => 'Approved',
                        3 => 'Rejected',
                        default => $state,
                    })
                    ->color(fn ($state): string => match ($state) {
                        0 => 'secondary',
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('leave_evidence')
                    ->searchable(),
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
