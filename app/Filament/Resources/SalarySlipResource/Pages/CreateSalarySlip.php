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
        // Setelah create, langsung kembali ke halaman index (table)
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): SalarySlip
    {
        foreach ($data['components'] as $component) {
            SalarySlip::create([
                'employee_id' => $data['employee_id'],
                'periode' => $data['periode'],
                'salary_component_id' => $component['salary_component_id'],
                'amount' => $component['amount'],
                // payroll_id otomatis diisi oleh event creating
            ]);
        }

        // return satu dummy untuk Filament (tidak terlalu dipakai)
        return new SalarySlip();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $employeeId = $data['employee_id'] ?? null;

        if (!isset($data['components']) || empty($data['components'])) {
            $basicSalary = \App\Models\Employee::find($employeeId)?->basic_salary ?? 0;

            $data['components'] = [
                [
                    'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'Basic Salary')->value('id'),
                    'component_type' => \App\Models\SalaryComponent::where('component_name', 'Basic Salary')->value('component_type'),
                    'amount' => $basicSalary,
                ],
                [
                    'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'PPh 21')->value('id'),
                    'component_type' => \App\Models\SalaryComponent::where('component_name', 'PPh 21')->value('component_type'),
                    'amount' => 0,
                ],
            ];
        }

        return $data;
    }
}
