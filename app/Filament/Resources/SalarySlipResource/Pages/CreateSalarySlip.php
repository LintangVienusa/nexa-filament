<?php

namespace App\Filament\Resources\SalarySlipResource\Pages;

use App\Filament\Resources\SalarySlipResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;
use App\Models\SalarySlip;
use App\Services\BpjsKesehatanService;
use App\Services\BpjsKetenagakerjaanService;
use App\Models\Payroll;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateSalarySlip extends CreateRecord
{
    protected static string $resource = SalarySlipResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $periodeCarbon = $data['periode']
            ? Carbon::createFromFormat('F Y', $data['periode'])
            : Carbon::now();
        $periodeString = $periodeCarbon->format('F Y');

        $payroll = Payroll::where('employee_id', $data['employee_id'])
            ->where('periode', $periodeString)
            ->first();

        if ($payroll) {
            Notification::make()
                ->title('Salary Slip Creation Failed')
                ->body("Payroll for the {$periodeString} period already exists.")
                ->danger()
                ->send();

            $this->halt(); // stop proses create
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): SalarySlip
    {

        // $periodeCarbon = $data['periode']
        //                     ? Carbon::parse($data['periode'])
        //                     : Carbon::now();

        // $periodeString = $periodeCarbon->format('F Y');

        // $payroll = Payroll::where('employee_id', $data['employee_id'])
        // ->where('periode', $periodeString)
        // ->first();

        foreach ($data['components'] as $component) {
            $salaryComponent = \App\Models\SalaryComponent::find($component['salary_component_id']);
    
            $potonganAlpha = $component['no_attendance'] ?? 0; 
            $overtime_hours = $component['overtime_hours'] ?? 0; 
            
            $baseAmount    = $component['amount'] ?? 0;
            
            if ($salaryComponent) {
                if ($salaryComponent->component_name === 'No Attendance') {
                    $noAttendance = $component['no_attendance'] ?? 0;
                    $amount = $noAttendance * $baseAmount;
                } elseif ($salaryComponent->component_name === 'Overtime') {
                    $overtimeHours = $component['overtime_hours'] ?? 0;
                    $amount = $overtimeHours * $baseAmount;
                }else{
                    $amount = $baseAmount;
                }
            }else{
                
                    $amount = $baseAmount;
            }
            SalarySlip::create([
                'employee_id' => $data['employee_id'],
                'periode' => $data['periode'],
                'salary_component_id' => $component['salary_component_id'],
                'amount' => $amount,
            ]);
        }


         

            $employee    = \App\Models\Employee::find($data['employee_id']);
            $basicSalary = $employee?->basic_salary ?? 0;

            $bpjsService = app(BpjsKesehatanService::class); // ⬅️ inisialisasi
            $bpjsKetenagakerjaanService = app(BpjsKetenagakerjaanService::class); // ⬅️ inisialisasi


            $tunjangan =  0;

            $hasil = $bpjsService->hitung($basicSalary, $tunjangan);
            $hasilkk = $bpjsKetenagakerjaanService->hitungkk($basicSalary);

            $allow_bpjs_kesehatan     = $hasil['perusahaan'];
            $deduction_bpjs_kesehatan = $hasil['total_iuran'];

            
            $allow_bpjs_kk    = $hasilkk['total_perusahaan'];
            $deduction_bpjs_kk = $hasilkk['total_iuran'];
            SalarySlip::create([
                        'employee_id'         => $data['employee_id'],
                        'periode'             => $data['periode'],
                        'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'BPJS Kesehatan')->where('component_type', '0')->value('id'),
                        'component_type'      => 0, 
                        'amount'              => $allow_bpjs_kesehatan,
                        'payroll_id'          => $data['payroll_id'] ?? null,
                    ]);
            
            SalarySlip::create([
                        'employee_id'         => $data['employee_id'],
                        'periode'             => $data['periode'],
                        'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'BPJS Kesehatan')->where('component_type', '1')->value('id'),
                        'component_type'      => 1, 
                        'amount'              => $deduction_bpjs_kesehatan,
                        'payroll_id'          => $data['payroll_id'] ?? null,
                    ]);

            SalarySlip::create([
                        'employee_id'         => $data['employee_id'],
                        'periode'             => $data['periode'],
                        'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'BPJS Ketenagakerjaan')->where('component_type', '0')->value('id'),
                        'component_type'      => 1, 
                        'amount'              => $allow_bpjs_kk,
                        'payroll_id'          => $data['payroll_id'] ?? null,
                    ]);
            
            SalarySlip::create([
                        'employee_id'         => $data['employee_id'],
                        'periode'             => $data['periode'],
                        'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'BPJS Ketenagakerjaan')->where('component_type', '1')->value('id'),
                        'component_type'      => 1, 
                        'amount'              => $deduction_bpjs_kk,
                        'payroll_id'          => $data['payroll_id'] ?? null,
                    ]);


         // ==== Hitung PPh21 ====

                $basicSalary = SalarySlip::query()
                                ->join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
                                ->where('SalarySlips.employee_id', $data['employee_id'])
                                ->where('SalarySlips.periode', $data['periode'])
                                ->where('SalaryComponents.component_name', 'Basic Salary')
                                ->value('SalarySlips.amount') ?? 0;

                $allowance = SalarySlip::query()
                            ->join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
                            ->where('SalarySlips.employee_id', $data['employee_id'])
                            ->where('SalarySlips.periode', $data['periode'])
                            ->where('SalaryComponents.component_type', 0) // allowance
                            ->sum('SalarySlips.amount');

                $overtime = SalarySlip::query()
                            ->join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
                            ->where('SalarySlips.employee_id', $data['employee_id'])
                            ->where('SalarySlips.periode', $data['periode'])
                            ->where('SalaryComponents.component_name', 'Overtime')
                            ->value('SalarySlips.amount') ?? 0;

            $allowance   = $allowance ?? 0;
            $overtime    = $overtime ?? 0;
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

                

                // Total Allowance
                $ta = SalarySlip::where('employee_id', $data['employee_id'])
                    ->where('periode', $data['periode'])
                    ->whereHas('salaryComponent', function ($q) {
                        $q->where('component_type', 0); // Allowance
                    })
                    ->sum('amount');

                // Total Deduction
                $td = SalarySlip::where('employee_id', $data['employee_id'])
                    ->where('periode', $data['periode'])
                    ->whereHas('salaryComponent', function ($q) {
                        $q->where('component_type', 1); // Deduction
                    })
                    ->sum('amount');

                $payroll = Payroll::where('employee_id', $data['employee_id'])
                    ->where('periode', $data['periode'])
                    ->first();
                
                if ($payroll) {
                        $total = $ta - $td;
                        DB::connection('mysql_employees')
                            ->table('Payrolls')
                            ->where('id', $payroll->id)
                            ->update(['salary_slips_created' => $total, 'salary_slips_approved' => $total]);
                        // $payroll->salary_slips_created = (int)$ta - (int)$td; // allowance - deduction
                        // $payroll->save();

                        $payroll->refresh();
                        
                    }
                
            }


        return new SalarySlip();
    }

    
}
