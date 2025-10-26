<?php

namespace App\Filament\Resources\SalarySlipResource\Pages;

use App\Filament\Resources\SalarySlipResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;
use App\Models\SalarySlip;
use App\Services\BpjsKesehatanService;
use App\Services\BpjsKetenagakerjaanService;
use App\Services\FixedAllowanceService;
use App\Models\Payroll;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Services\Pph21Service;

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
            $marital_status = $employee?->marital_status ?? 0;
            $number_of_children = $employee?->number_of_children ?? 0;

            $bpjsService = app(BpjsKesehatanService::class); // ⬅️ inisialisasi
            $bpjsKetenagakerjaanService = app(BpjsKetenagakerjaanService::class); // ⬅️ inisialisasi
            $fixedallowance = app(FixedAllowanceService::class); // ⬅️ inisialisasi
            

            $fa = $fixedallowance->hitung($basicSalary, $marital_status, $number_of_children);

            $marriage_allowance = $fa['marriage_allowance'] ?? 0;
            $child_allowance = $fa['child_allowance'];
            $fixed_allowance = $fa['Fixed_allowance'];

            $hasil = $bpjsService->hitung($basicSalary, $fixed_allowance);
            $hasilkk = $bpjsKetenagakerjaanService->hitung($basicSalary);

            $allow_bpjs_kesehatan     = $hasil['perusahaan'];
            $deduction_bpjs_kesehatan = $hasil['total_iuran'];

            $jp_company    = $hasilkk['jp_company'];
            $jp_employee    = $hasilkk['jp_employee'];
            $deductin_jp = $jp_company + $jp_employee;

            
            $jht_company    = $hasilkk['jht_employee'];
            $jht_employe    = $hasilkk['jht_employee'];
            $deductin_jht = $jht_company + $jht_employe;

            
            $jkk_company    = $hasilkk['jkk_company'];
            $jkm_company    = $hasilkk['jkm_company'];

            $deduction_bpjs_kk = $deductin_jp+$deductin_jht+$jkk_company+$jkm_company;


                // SalarySlip::create([
                //         'employee_id'         => $data['employee_id'],
                //         'periode'             => $data['periode'],
                //         'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'Marriage Allowance')->where('component_type', '0')->value('id'),
                //         'component_type'      => 0, 
                //         'amount'              => $marriage_allowance,
                //         'payroll_id'          => $data['payroll_id'] ?? null,
                //     ]);

            
                // SalarySlip::create([
                //         'employee_id'         => $data['employee_id'],
                //         'periode'             => $data['periode'],
                //         'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'Child Allowance')->where('component_type', '0')->value('id'),
                //         'component_type'      => 0, 
                //         'amount'              => $marriage_allowance,
                //         'payroll_id'          => $data['payroll_id'] ?? null,
                //     ]);
            
            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'BPJS Kesehatan')->where('component_type', '0')->value('id'),
            //             'component_type'      => 0, 
            //             'amount'              => $allow_bpjs_kesehatan,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            
            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'BPJS Kesehatan')->where('component_type', '1')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $deduction_bpjs_kesehatan,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);

            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JHT BPJS TK')->where('component_type', '0')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $jht_company,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            

            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JHT BPJS TK')->where('component_type', '1')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $deductin_jht,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);

            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JP BPJS TK')->where('component_type', '0')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $jp_company,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            

            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JP BPJS TK')->where('component_type', '1')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $deductin_jp,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            
            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JKK BPJS TK')->where('component_type', '0')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $jkk_company,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            

            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JKK BPJS TK')->where('component_type', '1')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $jkk_company,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            
            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JKM BPJS TK')->where('component_type', '0')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $jkm_company,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            

            // SalarySlip::create([
            //             'employee_id'         => $data['employee_id'],
            //             'periode'             => $data['periode'],
            //             'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'JKM BPJS TK')->where('component_type', '1')->value('id'),
            //             'component_type'      => 1, 
            //             'amount'              => $jkm_company,
            //             'payroll_id'          => $data['payroll_id'] ?? null,
            //         ]);
            


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

            $bpjs = $deduction_bpjs_kesehatan+$deduction_bpjs_kk ?? 0;
            
            $pph21 = app(Pph21Service::class);
            $marital_status =0;
            $number_of_children=0;
            $bpjs = 0;
            $hpph21 = $pph21->hitung($basicSalary, $allowance,$marital_status, $number_of_children, $bpjs);
            $pkp = $hpph21['taxable_income'];
            $taxmount = $hpph21['monthly_pph21'];
            $positionalallowance = $hpph21['position_cost'];
            if ($pkp > 0) {

                // SalarySlip::create([
                //     'employee_id'         => $data['employee_id'],
                //     'periode'             => $data['periode'],
                //     'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'Positional Allowance')->value('id'),
                //     'component_type'      => 0, 
                //     'amount'              => $positionalallowance,
                //     'payroll_id'          => $data['payroll_id'] ?? null,
                // ]);
                
                // SalarySlip::create([
                //     'employee_id'         => $data['employee_id'],
                //     'periode'             => $data['periode'],
                //     'salary_component_id' => \App\Models\SalaryComponent::where('component_name', 'PPh 21')->value('id'),
                //     'component_type'      => 1, 
                //     'amount'              => $taxmount,
                //     'payroll_id'          => $data['payroll_id'] ?? null,
                // ]);

                

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
