<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\HomeConnect;
use App\Models\BastProject;
use App\Models\Employee;
use Carbon\Carbon;

class DownloadBAService
{

    
    /**
     * Create a new class instance.
     */
    public function downloadBA($record)
    {
        
        $HomeConnect = HomeConnect::where('id_pelanggan', $record->id_pelanggan)
                    ->first();
        $bast_id = $HomeConnect->bast_id;
        $bast = BastProject::where('bast_id', $bast_id)
                    ->first();
        
        $employee = Employee::where('email',$HomeConnect->updated_by)->first();
        
        if($employee->middle_name != ''){
            $fullname = $employee->first_name." ".$employee->middle_name." ".$employee->last_name;
        }else{
            $fullname = $employee->first_name." ".$employee->last_name;
        }
        
        $hariTanggal = Carbon::parse($bast->bast_date)
            ->locale('id')
            ->translatedFormat('l, d F Y');

        $jam = Carbon::parse($HomeConnect->updated_at)->format('H.i.s');
        
        $path = public_path('storage/' . $HomeConnect->foto_label_id_plg);
        if($HomeConnect->foto_label_id_plg !=''){
            $path = str_replace('\\', '/', $path);
            $idpelanggan = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        }else{
            $idpelanggan ='';
        }

        $path2 = public_path('storage/' . $HomeConnect->foto_qr);
        if($HomeConnect->foto_qr !=''){
            $path2 = str_replace('\\', '/', $path2);
            $qr = 'data:image/png;base64,' . base64_encode(file_get_contents($path2));
        }else{
            $qr = '';
        }

        $path3 = public_path('storage/' . $HomeConnect->foto_label_odp);
        if($HomeConnect->foto_label_odp !=''){
            $path3 = str_replace('\\', '/', $path3);
            $odp = 'data:image/png;base64,' . base64_encode(file_get_contents($path3));
        }else{
            $odp = '';
        }

        $path4 = public_path('storage/' . $HomeConnect->foto_sn_ont);
        if($HomeConnect->foto_sn_ont !=''){
            
            $path4 = str_replace('\\', '/', $path4);
            $foto_sn_ont = 'data:image/png;base64,' . base64_encode(file_get_contents($path4));
        }else{
            $foto_sn_ont = '';
        }

        $html = "
            <html>
            <head>
                <style>
                    body { font-family: sans-serif; font-size: 14px; }
                    .center { text-align: center; }
                    .bold { font-weight: bold; }
                    .title { font-size: 16px; font-weight: bold; }
                    table { width: 100%; border-collapse: collapse; }
                    .box { border: 1px solid #000; height: 230px; }
                    .page-break {
                        page-break-before: always;
                    }
                </style>
            </head>
            <body>
            <br>
                <table style='font-size: 24px;'>
                    <tr>
                        <td><img src='".public_path('assets/images/Invoice Permit_20251008_233910_0002.png')."' height='70'></td>
                        <td class='center'>
                            <div class='title'>BERITA ACARA</div>
                            <div class='title'>PEMASANGAN DAN INSTALASI KABEL RUMAH</div>
                            <div class='title'>STARLITE</div>
                        </td>
                        <td class='right'><img src='".public_path('assets/images/Picture1.png')."' height='80'></td>
                    </tr>
                </table>

                <br>
                <br>
                <br>
                <br>

                <div style='width: 100%;'>
                    <table style='width: auto; margin-left: 0; text-align: left;'>
                        <tr>
                            <td class='bold' style='text-align: left;'>Tanggal</td>
                            <td style='text-align: left;'>: {$hariTanggal}</td>
                        </tr>
                        <tr>
                            <td class='bold' style='text-align: left;'>Jam</td>
                            <td style='text-align: left;'>: {$jam}</td>
                        </tr>
                    </table>
                </div>

                <br><br>

                <div class='bold' style='text-decoration: underline;'>DATA PETUGAS</div>
                <br>
                <table style='width:50%;'>
                    <tr><td style='text-align: left;'>Nama Petugas</td><td style='text-align: left;'>: {$fullname}</td></tr>
                    <tr><td style='text-align: left;'>Email Petugas</td><td style='text-align: left;'>: {$HomeConnect->updated_by}</td></tr>
                </table>

                <br>

                <div class='bold' style='text-decoration: underline;  text-left: right;'>DATA PELANGGAN</div>
                <br>
                <table style='width:50%;'>
                    <tr><td style='text-align: left;'>Nama Pelanggan</td><td style='text-align: left;'>: {$HomeConnect->name_pelanggan}</td></tr>
                    <tr><td style='text-align: left;'>ID Pelanggan</td><td style='text-align: left;'>: {$HomeConnect->id_pelanggan}</td></tr>
                    <tr><td style='text-align: left;'>SN ONT</td><td style='text-align: left;'>: {$HomeConnect->sn_ont}</td></tr>
                    <tr><td style='text-align: left;'>Datek ODP</td><td style='text-align: left;'>: {$HomeConnect->odp_name}</td></tr>
                    <tr><td style='text-align: left;'>Port ODP</td><td style='text-align: left;'>: {$HomeConnect->port_odp}</td></tr>
                    <tr><td style='text-align: left;'>Site Pekerjaan</td><td style='text-align: left;'>: {$HomeConnect->site}</td></tr>
                </table>

                <br><br>

                <table border='1'>
                    <tr class='center bold'>
                        <td>FOTO LABEL ID PELANGGAN DI ODP</td>
                        <td>FOTO STIKER QR DI RUMAH PELANGGAN</td>
                    </tr>
                    <tr>
                        <td class='box' center>
                            <img src='{$idpelanggan}' width='50%'  >
                        </td>
                        <td class='box' center>
                           <img src='{$qr}' width='50%'  >
                        </td>
                    </tr>
                
                </table>
                <div style='page-break-before: always;'></div>
                <table border='1'>
                    <tr class='center bold'>
                        <td>FOTO ODP</td>
                        <td>FOTO SN ONT</td>
                    </tr>
                    <tr>
                        <td class='box' center>
                            <img src='{$odp}' width='50%' height='25%'>
                        </td>
                        <td class='box' center>
                           <img src='{$foto_sn_ont}' width='50%' height='25%' >
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ";


            $pdf = Pdf::loadHTML($html)->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'chroot' => public_path(),
            ]);
            
            return $pdf->setPaper('A4', 'portrait');
        
    }
}
