<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Employee;
use App\Models\Invoice;
use Carbon\Carbon;

class DownloadInvoiceService
{
    public function downloadInvoice($record)
    {

        $record->load('items.service', 'customer');

        $total = $record->items->sum(fn($i) => $i->subtotal);
        $taxRate = $record->tax_rate ?? 0.10;
        $taxrateper = $record->tax_rate * 100;
        $tax = $total * $taxRate;
        $grandTotal = $total + $tax;

        $spellNumber  = null;
        $spellNumber  = function ($number) use (&$spellNumber )  {
            $number = abs($number);
            $words  = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

            if ($number < 12) {
                return " " . $words[$number];
            } elseif ($number < 20) {
                return $spellNumber ($number - 10) . " Belas";
            } elseif ($number < 100) {
                return $spellNumber (intval($number / 10)) . " Puluh" . $spellNumber ($number % 10);
            } elseif ($number < 200) {
                return " Seratus" . $spellNumber ($number - 100);
            } elseif ($number < 1000) {
                return $spellNumber (intval($number / 100)) . " Ratus" . $spellNumber ($number % 100);
            } elseif ($number < 2000) {
                return " Seribu" . $spellNumber ($number - 1000);
            } elseif ($number < 1000000) {
                return $spellNumber (intval($number / 1000)) . " Ribu" . $spellNumber ($number % 1000);
            } elseif ($number < 1000000000) {
                return $spellNumber (intval($number / 1000000)) . " Juta" . $spellNumber ($number % 1000000);
            } elseif ($number < 1000000000000) {
                return $spellNumber (intval($number / 1000000000)) . " Miliar" . $spellNumber (fmod($number, 1000000000));
            } else {
                return "Angka terlalu besar";
            }
        };
        // body {
        //                     font-family: Arial, sans-serif;
        //                     height: 100%;
        //                     margin: 0;
        //                     padding: 0;
        //                     background-color: #FFDEAD; /* coklat muda (wheat) */
        //                     position: relative;
        //                 }
        //                 .header { display: flex; align-items: left; }
        //                 .logo { height: 20rem; width: 20rem; margin-right: 30px; }
        //                 .title { font-size: 24px; font-weight: bold; }
        //                 .info { font-size: 12px; margin-top: 5px; }
        //                 .info_alamat { font-size: 12px; margin-top: 10px; }
        //                 .info_pt { font-size: 16px; font-weight: bold; margin-top: 5px; background-color: #ffffff;}
        //                 table { font-size: 14px; width: 100%; border-collapse: collapse; margin-top: 5px; }
        //                 th, td { border: none; padding: 5px; text-align: left; }
        //                 th { background-color: #f5f5f5; }
        //                 tfoot td { font-weight: bold; }
        //                 .footer { position: fixed; bottom: 20px; width: 100%; text-align: center; font-size: 12px; color: #888; }

        $html = '
                <html>
                <head>
                    <meta charset="UTF-8">
                        <title>Invoice</title>
                        <style>
                        @page {
                            margin: 0;
                        }

                        html, body {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                            background-color: #e8d1ad; /* coklat muda */
                        }

                        body {
                            padding: 40px;
                            position: relative;
                        }

                        .header {
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            margin-top: 0;
                            padding-top: 0;
                        }

                        .logo {
                            height: 100px;
                            margin-top: -10;
                            display: block;
                        }

                        .invoice-title {
                            font-family: Times New Roman, serif;
                            font-size: 40px;
                            font-weight: bold;
                            text-align: center;
                            display: flex;
                            align-items: center; 
                            justify-content: center;
                        }

                        .invoice-info {
                            margin-top: 20px;
                            background-color: #3a3a3a;
                            color: white;
                            padding: 10px 15px;
                            font-size: 14px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-radius: 5px;
                        }

                        .invoice-info .number {
                            font-weight: bold;
                        }

                        .to-section {
                            margin-top: 10px;
                            display: flex;
                            justify-content: space-between;
                            font-size: 14px;
                        }

                        .to-section .left strong {
                            display: block;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 0;
                            font-size: 14px;
                        }

                        thead {
                            background-color: #3a3a3a;
                            color: white;
                        }

                        thead th {
                            padding: 10px;
                            text-align: left;
                        }

                        tbody td {
                            padding: 8px 10px;
                            border-bottom: 1px solid #ccc;
                        }

                        .total-box {
                            background-color: #3a3a3a;
                            color: white;
                            width: 200px;
                            padding: 10px;
                            margin-left: auto;
                            margin-top: 40px;
                            border-radius: 5px;
                            font-size: 12px;
                        }

                        .total-box div {
                            margin-bottom: 4px;
                        }

                        /* Watermark logo samar di background */
                        .watermark {
                            position: fixed;
                            top: 35%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            opacity: 0.05;
                            z-index: 0;
                            width: 600px;
                        }
                    </style>
                </head>
                <body>
                    <img src="'. public_path('assets/images/Invoice Permit_20251008_233910_0002.png') .'" class="watermark" alt="Watermark">
                    <div class="header">
                        <table>
                            <td>
                                <img src="'. public_path('assets/images/Invoice Permit_20251008_233910_0002.png') .'" class="logo" alt="Logo">
                            </td>
                            <td class="invoice-title">
                                <div center >INVOICE</div>
                            </td>
                            
                        <table>
                    </div>

                    <table class="invoice-info">
                        <td class="number">01.51/DPNG/INV/STARLITE-PRM/2025</td>
                        <td class="date">DATE: 12/04/2025</td>
                    </table>
                    <div class="to-section">
                        <div class="left">
                            Kepada<br>
                            <strong>PT Integrasi Jaringan Ekosistem</strong>
                        </div>
                        <div class="right">
                            No PO : PO.2025.03.00149<br>
                            <strong>Project FTTH (Segment Poris)</strong>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ITEM DESCRIPTION</th>
                                <th>QTY</th>
                                <th>PRICE</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Pekerjaan Penarikan Kabel Fiber</td>
                                <td>1</td>
                                <td>Rp 10.000.000</td>
                                <td>Rp 10.000.000</td>
                            </tr>
                            <tr>
                                <td>Instalasi ODP</td>
                                <td>2</td>
                                <td>Rp 5.000.000</td>
                                <td>Rp 10.000.000</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="total-box">
                        <div>TOTAL</div>
                        <div>DP 20%</div>
                        <div>PPN (11%)</div>
                        <div>TOTAL DP INC. PPN</div>
                    </div>

                </body>
                </html>';

        $pdf = \PDF::loadHTML($html)->setPaper('A4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "Invoice-{$record->id}.pdf"
        );
    }
}