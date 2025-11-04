<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasOwnRecordPolicy;
use App\Models\Attendance;
use App\Models\Employee;
use App\Traits\HasNavigationPolicy;
use Spatie\Permission\Traits\HasPermissions;
use Filament\Notifications\Notification;

class LateList extends ListRecords 
{
    // use HasPermissions, HasOwnRecordPolicy, HasNavigationPolicy;
    protected static string $resource = AttendanceResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $title = 'Daftar Karyawan Terlambat';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Attendance::query()
                    ->where('status', 2) // status 2 = terlambat
                    ->orderByDesc('attendance_date')
            )
            ->columns([
                TextColumn::make('employee.employee_id')->label('NIK'),
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
                TextColumn::make('attendance_date')
                    ->label('Tanggal')
                    ->date(),
                TextColumn::make('check_in_time')
                    ->label('Check In'),
                TextColumn::make('check_out_time')
                    ->label('Check Out'),
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
            ])
            ->actions([
                Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Pilih Status Baru')
                            ->options([
                                '1' => 'Tetap Dihitung Hadir',
                                '2' => 'Tidak Hadir',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('keterangan')
                            ->label('Catatan')
                            ->rows(2)
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, Attendance $record): void {
                        $oldNotes = trim($record->notes ?? '');
                        $newNote = '[' . now()->format('d-m-Y H:i') . '] ' 
                            . (auth()->user()->name ?? 'Admin') 
                            . ': ' . ($data['keterangan'] ?? '');

                        $combinedNotes = $oldNotes
                            ? $oldNotes . "\n" . $newNote
                            : $newNote;
                        $record->update([
                            'status' => $data['status'],
                            'notes' => $combinedNotes ?? null,
                        ]);


                        Notification::make()
                            ->title('Status berhasil diperbarui!')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Perbarui Status Absensi')
                    ->modalButton('Update'),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->paginated([10, 25, 50]);
    }
}
