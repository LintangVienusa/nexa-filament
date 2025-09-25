<?php

namespace App\Filament\Resources\SalarySlipResource\Pages;

use App\Filament\Resources\SalarySlipResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\SalarySlip;

class CreateSalarySlip extends CreateRecord
{
    protected static string $resource = SalarySlipResource::class;

    

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): SalarySlip
    {
        foreach ($data['components'] as $component) {
            $potonganAlpha = $component['potongan_alpha'] ?? 0; 
            $overtime_hours = $component['overtime_hours'] ?? 0; 

            if($potonganAlpha != 0){
                $amount = $potonganAlpha * $component['amount'];
            }elseif($overtime_hours != 0){
                $amount = $overtime_hours * $component['amount'];
            }else{
                $amount =  $component['amount'];
            }
            SalarySlip::create([
                'employee_id' => $data['employee_id'],
                'periode' => $data['periode'],
                'salary_component_id' => $component['salary_component_id'],
                'amount' => $amount,
            ]);
        }

         // ==== Hitung PPh21 ====
            $employee    = \App\Models\Employee::find($data['employee_id']);
            $basicSalary = $employee?->basic_salary ?? 0;
            $allowance   = $data['allowance'] ?? 0;
            $overtime    = $data['overtime_pay'] ?? 0;
            $dependents  = $employee?->dependents ?? 0;

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

                SalarySlip::create([
                    'employee_id'         => $data['employee_id'],
                    'periode'             => $data['periode'],
                    'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'PPh 21')->value('id'),
                    'component_type'      => 1, 
                    'amount'              => $pph21Amount,
                    'payroll_id'          => $data['payroll_id'] ?? null,
                ]);
            }


        return new SalarySlip();
    }

    
}
