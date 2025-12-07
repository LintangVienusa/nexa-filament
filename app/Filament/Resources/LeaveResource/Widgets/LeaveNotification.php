<?php

namespace App\Filament\Resources\LeaveResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Leave;


class LeaveNotification extends BaseWidget
{
    // protected static string $view = 'filament.resources.leave-resource.widgets.leave-notification';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $jobTitle = $user->employee?->job_title;

        return $table
            ->query(
                Leave::query()
                    ->where('status', 0)
                    ->with('employee')
                    ->when($jobTitle === 'Manager', fn($q) => $q->whereHas('employee', fn($q2) => $q2->where('manager_id', $user->id)->where('org_id', $user->employee?->org_id)))
                    ->when($jobTitle === 'VP', fn($q) => $q->where('status', 1)->where('org_id', $user->employee?->org_id))
                    ->when(in_array($jobTitle, ['CEO','CTO']), fn($q) => $q->where('status', 2))
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Nama Karyawan'),
                Tables\Columns\TextColumn::make('leave_type')->label('Tipe Leave')
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
                                }),
                Tables\Columns\TextColumn::make('start_date')->label('Mulai')->date(),
                Tables\Columns\TextColumn::make('end_date')->label('Selesai')->date(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->action(function ($record) use ($jobTitle) {
                        if ($jobTitle === 'Manager') {
                            $record->update(['status' => 1]);
                        } elseif ($jobTitle === 'CTO') {
                            $record->update(['status' => 3]);
                        } elseif (in_array($jobTitle, ['CEO'])) {
                            $record->update(['status' => 5]);
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->action(function ($record) use ($jobTitle) {
                        if ($jobTitle === 'Manager') {
                            $record->update(['status' => 2]);
                        } elseif ($jobTitle === 'CTO') {
                            $record->update(['status' => 4]);
                        } elseif (in_array($jobTitle, ['CEO'])) {
                            $record->update(['status' => 6]);
                        }
                    }),
            ]);
    }
}
