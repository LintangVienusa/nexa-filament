<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Employee;
use App\Models\SalarySlip;
use Carbon\Carbon;

class DownloadSlipService
{
    public function downloadSlip($employeeId, $periode)
    {
        $employee = Employee::select('Employees.*', 'Organizations.divisi_name as divisi_name', 'Organizations.unit_name as unit_name')
            ->join('Organizations', 'Employees.org_id', '=', 'Organizations.id')
            ->where('Employees.employee_id', $employeeId)
            ->firstOrFail();

        $slips_allow = SalarySlip::select('SalarySlips.*', 'SalaryComponents.component_name')
            ->join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
            ->where('SalarySlips.employee_id', $employeeId)
            ->where('SalarySlips.periode', $periode)
            ->where('SalaryComponents.component_type', 0)
            ->get();

        $total = $slips_allow->sum('amount');

        $slips_decd = SalarySlip::select('SalarySlips.*', 'SalaryComponents.component_name')
            ->join('SalaryComponents', 'SalarySlips.salary_component_id', '=', 'SalaryComponents.id')
            ->where('SalarySlips.employee_id', $employeeId)
            ->where('SalarySlips.periode', $periode)
            ->where('SalaryComponents.component_type', 1)
            ->get();

        $total_dec = $slips_decd->sum('amount');
        $net_salary = $total-$total_dec;
        $date = Carbon::now()->format('Y/m/d H:m:s');

        // Buat HTML langsung tanpa blade
        $html = '
                <html>
                    <head>
                        <style>
                            @page {
                            margin: 0;
                        }

                        html, body {
                            margin: 0;
                            padding: 0;
                            font-family:  sans-serif;
                            background-color: #e9d0a9ff;  
                        }

                        body {
                            padding: 40px;
                            position: relative;
                        }
                            .header { display: flex; align-items: center; }
                            .logo { height: 80px; width: 300px; margin-right: 20px; }
                            .title { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 20px; font-weight: bold;  color: #888; }
                            .info { font-size: 12px; margin-top: 15px; }
                            .info_pt { font-size: 16px; font-weight: bold; margin-top: 20px; }
                            table { font-size: 14px; width: 100%; border-collapse: collapse; margin-top: 15px; }
                            th, td { border: none; padding: 5px; text-align: left; }
                            th { background-color: #dabe92ff; }
                            tfoot td { font-weight: bold; }
                            .footer { position: fixed; bottom: 20px; width: 100%; text-align: center; font-size: 12px; color: #888; }
                        .watermark {
                            position: fixed;
                            bottom: 10px;
                            left: 0;
                            width: 100%;
                            height: 0%;
                            pointer-events: none;
                            opacity: 0.11; 
                            z-index: 0;
                            }

                            .watermark-row {
                            position: absolute;
                            }

                            .watermark-row img {
                            width: 45rem;
                            height: auto;
                            }

                            
                            .watermark-left {
                            bottom: 0;
                            left: -230px;
                            margin-bottom: -180px;
                            }

                           
                            .watermark-center {
                            bottom: 10px; 
                            left: 30px;
                            transform: translateX(-50%);
                            }

                            
                            .watermark-right {
                            bottom: 240px;
                            right: -230px;
                            }

                            
                            .rotate-left {
                            transform: rotate(0deg);
                            }
                            .rotate-pusat {
                            transform: rotate(0deg);
                            }

                            .rotate-right {
                            transform: rotate(180deg);
                            }
                        </style>
                    </head>
                <body>
                    <div class="watermark">
                    
                    <div class="watermark-row watermark-left rotate-pusat">
                        <img src="'. public_path('assets/images/LOGO PT DAPOER POESAT NUSANTARA-08.png') .'" alt="Logo">
                    </div>
                        <div class="watermark-row watermark-center rotate-right">
                        <img src="'. public_path('assets/images/LOGO PT DAPOER POESAT NUSANTARA-08.png') .'" alt="Logo">
                    </div>
                    <div class="watermark-row watermark-right rotate-left">
                        <img src="'. public_path('assets/images/LOGO PT DAPOER POESAT NUSANTARA-08.png') .'" alt="Logo">
                    </div>
                    </div>
                    <div style="font-size: 12px; text-align: right;">
                        '. $date.'
                    </div>

                    <div class="header">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: left; font-weight: bold; font-size: 16px;">
                                    SALARY SLIP
                                </td>
                                <td style="text-align: right; font-size: 20px; font-weight: bold;">
                                    PRIVATE & CONFIDENTIAL
                                </td>
                            </tr>
                        </table>
                    </div>
                    <img src="' . public_path('assets/images/Invoice Permit_20251008_233910_0002.png') . '" class="logo">
                        
                    <div class="info" style="display: flex; justify-content: space-between; gap: 10px;">
                        <table style="width: 50%; float:left; border-collapse: collapse; ">
                            <thead>
                                <tr >
                                    <th colspan="2">Employee Details</th>
                                </tr>
                            </thead>
                            <tr>
                                <td><strong>Name</strong></td>
                                <td>' . $employee->first_name . ' ' . $employee->middle_name . ' ' . $employee->last_name . '</td>
                            </tr>
                            <tr>
                                <td><strong>Address</strong></td>
                                <td>' . $employee->address . '</td>
                            </tr>
                            <tr>
                                <td><strong>Dept/Unit</strong></td>
                                <td>' . $employee->divisi_name . '/' . $employee->unit_name . '</td>
                            </tr>
                            <tr>
                                <td><strong>Job Title</strong></td>
                                <td>' . $employee->job_title . '</td>
                            </tr>
                        </table>
                        <table style="width: 40%; float:right; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th colspan="2" style="text-align:left;  padding:5px;">Direct Credit Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Bank</strong></td>
                                    <td>' . $employee->bank_account_name . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Account Number</strong></td>
                                    <td>' . $employee->bank_account_no . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Account Name</strong></td>
                                    <td>' . $employee->first_name . '</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div style="clear:both;"></div>
                    </div>

                    <div class="info" style="margin-top: 8px;">
                        <table style="width: 100%; border-collapse: collapse; text-align:left;">
                            <tbody>
                                <tr>
                                    <td colspan="2" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px;">
                                        <strong>Periode: '. $periode .'</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="info" style="display: flex; justify-content: space-between; gap: 10px;">
                        <table style="width: 48%; float:left;  border-collapse: collapse; ">
                            <thead>
                                <tr >
                                    <th style="font-align:Left">Eanings</th>
                                    <th  style="font-align:Right">Amount</th>
                                </tr>
                            </thead>
                            
                                <tbody>';
                                foreach ($slips_allow as $slip) {
                                        $componentName = $slip->component_name ?? 'Unknown';
                                        $amount = number_format($slip->amount, 0, ',', '.');
                                        $html .= "
                                            <tr>
                                                <td>{$componentName}</td>
                                                <td style='font-align:right;'>Rp {$amount}</td>
                                            </tr>";
                                    }
                            
                       $html .= ' 
                                    <tr>
                                        <th style="font-align:Left">Total Eanings</th>
                                        <th style="text-align:right;"><strong>Rp '.number_format($total,0,',','.').'</strong></th>
                                    <tr>
                                </tbody>
                            </table>

                            <table style="width: 45%; float:right;  border-collapse: collapse; ">
                            <thead>
                                <tr >
                                    <th style="font-align:Left">Deductions</th>
                                    <th  style="font-align:Right">Amount</th>
                                </tr>
                            </thead>
                            
                                <tbody>';
                                foreach ($slips_decd as $slip_d) {
                                        $componentNamed = $slip_d->component_name ?? 'Unknown';
                                        $amountd = number_format($slip_d->amount, 0, ',', '.');
                                        $html .= "
                                            <tr>
                                                <td>{$componentNamed}</td>
                                                <td style='font-align:right;'>Rp {$amountd}</td>
                                            </tr>";
                                    }
                            
                       $html .= ' 
                                     <tr>
                                        <th style="font-align:Left">Total Deductions</th>
                                        <th style="text-align:right;"><strong>Rp '.number_format($total_dec,0,',','.').'</strong></th>
                                    <tr>
                                    
                                </tbody>
                            </table>
                        
                            <div style="clear:both;"></div>
                                </div>
                                <div class="info" style="margin-top: 8px; background-color: #dabe92ff;">
                                    <table style="width: 100%;  text-align: center;">
                                        <thead>
                                            <tr>
                                                <td  style="text-align: center;">
                                                    <strong>Total Net Salary </strong>
                                                </td>
                                                
                                            </tr>
                                            <tr>
                                                <td  style="color:#888; text-align: center;">
                                                    <strong>Gross Earnings - Total Deduction</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td  style="color:#A0522D; font-size:24px; text-align: center;">
                                                    <strong stlye="color:#A0522D;";>Rp '.number_format($net_salary,0,',','.').'</strong>
                                                </td>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                        ';
       

        // Render ke PDF
        $pdf = \PDF::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf;
    }
}