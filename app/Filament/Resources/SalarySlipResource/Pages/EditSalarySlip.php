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
        // Ambil semua komponen dari employee_id yang sama di semua SalarySlip
        $employeeId = $this->record->employee_id;

        $allComponents = SalarySlip::where('employee_id', $employeeId)
            ->pluck('salary_component_id') // ambil field JSON
            ->flatten(1)          // gabungkan array JSON
            ->unique('salary_component_id') // pastikan unik per component
            ->values()
            ->toArray();

        $data['components'] = $allComponents; // isi Repeater
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
        // Pastikan amount disimpan sebagai integer
        $data['components'] = collect($data['components'] ?? [])->map(function ($item) {
            $item['amount'] = (int) ($item['amount'] ?? 0);
            $item['type'] = (int) ($item['type'] ?? 0);
            return $item;
        })->toArray();

        return $data;
    }
}
