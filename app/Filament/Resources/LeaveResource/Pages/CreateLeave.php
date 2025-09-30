<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Exceptions\PreventAction;
use Carbon\Carbon;

use App\Models\Leave;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employeeId = $data['employee_id'] ?? null;
        $employee = auth()->user()->employee;

        if ($employeeId && $employee) {
            $dateJoin = Carbon::parse($employee->date_of_joining);
            $yearsWorked = $dateJoin->diffInYears(now());

            // Belum 1 tahun kerja
            if ($data['leave_type'] == 1 && $yearsWorked < 1) {
                Notification::make()
                    ->title('Belum genap 1 tahun bekerja')
                    ->body('Anda belum bisa mengambil Annual Leave.')
                    ->warning()
                    ->send();

                $this->halt(); // batal create
            }

            // Cek saldo cuti tahunan
            if ($data['leave_type'] == 1) {
                $balance = Leave::getAnnualLeaveBalance($employeeId);

                if ($balance <= 0) {
                    Notification::make()
                        ->title('Saldo cuti tahunan Anda 0')
                        ->body('Tidak bisa membuat Annual Leave.')
                        ->danger()
                        ->send();

                    $this->halt(); // batal create
                }
            }
        }

        return $data;
    }
}
