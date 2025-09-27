<?php

namespace App\Filament\Resources\SalarySlipResource\Pages;

use App\Filament\Resources\SalarySlipResource;
use App\Models\SalarySlip;
use App\Models\SalaryComponent;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSalarySlip extends EditRecord
{
    protected static string $resource = SalarySlipResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        
        $salarySlips = SalarySlip::where('employee_id', $data['employee_id'])
            ->where('periode', $data['periode'])
            ->whereNotIn('salary_component_id',[9] )
            ->get();

        $data['components'] = $salarySlips->map(function ($slip) {
            return [
                'salary_component_id' => $slip->salary_component_id,
                'component_type'      => $slip->component_type,
                'amount_display'              => $slip->amount,
                'amount'          => $slip->amount,
            ];
        })->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        $employeeId = $this->record->employee_id;
        $periode = $this->record->periode;

        foreach ($data['components'] as $component) {
            \App\Models\SalarySlip::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'periode' => $periode,
                    'salary_component_id' => $component['salary_component_id'],
                ],
                [
                    'amount' => (int) preg_replace('/[^0-9]/', '', $component['amount']),
                ]
            );
        }

        
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        
        $data['components'] = collect($data['components'] ?? [])->map(function ($item) {
            $item['amount'] = (int) ($item['amount'] ?? 0);
            $item['type'] = (int) ($item['type'] ?? 0);
            return $item;
        })->toArray();

        $employeeId = $this->record->employee_id;
        $periode = $this->record->periode;

        // ==== Hitung PPh21 ====
        $basicSalary    = \App\Models\SalarySlip::join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
                        ->where('SalarySlips.employee_id', $employeeId)
                        ->where('SalarySlips.periode', $periode)
                        ->where('SalaryComponents.component_name','Basic Salary')
                        ->first();

        $basicSalary = $SalarySlip?->amount ?? 0;

        $allowance = \App\Models\SalarySlip::join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
                        ->where('SalarySlips.employee_id', $employeeId)
                        ->where('SalarySlips.periode', $periode)
                        ->whereNotIn('SalaryComponents.component_name', ['Basic Salary', 'Overtime']) 
                        ->where('SalaryComponents.component_type', 0)
                        ->sum('SalarySlips.amount');

        // Ambil overtime (misalnya component_type = 2)
        $overtime = \App\Models\SalarySlip::join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
                ->where('SalarySlips.employee_id', $employeeId)
                ->where('SalarySlips.periode', $periode)
                ->where('SalaryComponents.component_name','Overtime')
                ->sum('amount');
        
        $dependents  =  0;
        $bruto        = $basicSalary + $allowance + $overtime;
        $biayaJabatan = min(0.05 * $bruto, 500000);
        $ptkp         = 4500000 + ($dependents * 3750000 / 12);
        $pkp          = $bruto - $biayaJabatan - $ptkp;

           

        if ($pkp > 0) {
            $pkpTahunan = $pkp * 12;
            $tax = 0;

            if ($pkpTahunan <= 60000000) {
                $tax = 0.05 * $pkpTahunan;
            } elseif ($pkpTahunan <= 250000000) {
                $tax = 0.05 * 60000000 + 0.15 * ($pkpTahunan - 60000000);
            } elseif ($pkpTahunan <= 500000000) {
                $tax = 0.05 * 60000000 + 0.15 * (250000000 - 60000000) + 0.25 * ($pkpTahunan - 250000000);
            } else {
                $tax = 0.05 * 60000000
                    + 0.15 * (250000000 - 60000000)
                    + 0.25 * (500000000 - 250000000)
                    + 0.30 * ($pkpTahunan - 500000000);
            }

            $pph21Amount = round($tax / 12);

            \App\Models\SalarySlip::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'periode' => $periode,
                    'salary_component_id' => 9,
                ],
                [
                    'amount' => (int) preg_replace('/[^0-9]/', '', $pph21Amount),
                ]
            );
        }

        return $data;
    }
}
