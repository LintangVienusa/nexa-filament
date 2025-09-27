<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Employee;
use App\Models\SalarySlip;

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

        // Buat HTML langsung tanpa blade
        $html = '
                <html>
                    <head>
                        <style>
                            body {font-family: DejaVu Sans, Helvetica, Arial, sans-serif; margin: 20px; }
                            .header { display: flex; align-items: center; }
                            .logo { height: 80px; width: 300px; margin-right: 20px; }
                            .title { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 24px; font-weight: bold;  color: #888; }
                            .info { font-size: 12px; margin-top: 15px; }
                            .info_pt { font-size: 16px; font-weight: bold; margin-top: 20px; }
                            table { font-size: 14px; width: 100%; border-collapse: collapse; margin-top: 15px; }
                            th, td { border: none; padding: 5px; text-align: left; }
                            th { background-color: #F0F8FF; }
                            tfoot td { font-weight: bold; }
                            .footer { position: fixed; bottom: 20px; width: 100%; text-align: center; font-size: 12px; color: #888; }
                        </style>
                    </head>
                <body>

                    <div class="header">
                    <div class="title">
                            SALARY SLIP
                        </div>
                        <img src="' . public_path('assets/images/Kop Surat Logo PT Nexanira Biru.png') . '" class="logo">
                        
                    </div>
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
                            <div class="info" style="margin-top: 8px; background-color: #F0F8FF;">
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
                                            <td  style="color:#1E90FF; font-size:24px; text-align: center;">
                                                <strong>Rp '.number_format($net_salary,0,',','.').'</strong>
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