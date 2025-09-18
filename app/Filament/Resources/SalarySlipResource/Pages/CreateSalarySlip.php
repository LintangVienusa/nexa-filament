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
}
