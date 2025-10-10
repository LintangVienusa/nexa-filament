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
        $taxRate = $record->tax_rate ?? 0.11;
        $taxrateper = $record->tax_rate * 100;
        $dp = 0.20;
        $dpteper = $total * $dp;
        $tax = $total * $taxRate;
        $totaldp = $tax + $dpteper;
        $grandTotal = ($total ) - $dpteper;


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
                            margin-top: -30px;
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
                            align-items: center; 
                            justify-content: center;
                        }

                        .footer {
                            justify-content: space-between; 
                            align-items: center;            
                            margin-top: 10px;
                            background-color: #3a3a3a;
                            color: white;
                            padding: 5px 15px;
                            font-size: 12px;
                            border-radius: 5px;
                            width: 100%;
                            flex-wrap: nowrap;
                            border: none;  
                            font-style:italic;
                        }

                        .invoice-info {
                            justify-content: space-between; 
                            align-items: center;            
                            margin-top: 10px;
                            background-color: #3a3a3a;
                            color: white;
                            padding: 5px 15px;
                            font-size: 14px;
                            border-radius: 5px;
                            width: 100%;
                            flex-wrap: nowrap;
                            border: none;  
                        }

                        .invoice-info .number,
                        .invoice-info .date {
                            white-space: nowrap; 
                            text-decoration: none; /* hilangkan underline jika ada */
                            border: none;  
                        }

                        .to-section {
                            margin-top: 5px;
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
                            border: none; 
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
                            padding: 5px 5px;
                            border-bottom: 1px solid #ccc;
                            border: none; 
                        }

                        .total-box {
                            position: absolute;
                            right: 40px;       
                            bottom: 100px; 
                            background-color: #3a3a3a;
                            color: white;
                            width: 250px;
                            padding: 10px;
                            margin-left: auto;
                            margin-top: 50px;
                            margin-bottom: 10px;
                            border-radius: 5px;
                            font-size: 12px;
                        }

                        .total-box div {
                            margin-bottom: 5px;
                        }

                         
                        
                        .total-box-footer {
                                position: fixed;
                                bottom: 70;
                                left: 40px;
                                right: 80px;
                                width: 90%;
                                background-color: #3a3a3a;
                                color: white;
                                text-align: left;
                                font-size: 12px;
                                padding: 8px 0;
                                font-style: italic;
                                border-radius: 5px;
                                border-top:none;
                            }

                        /* Watermark logo samar di background */
                        .watermark {
                            position: fixed;
                            bottom: 10px;
                            left: 0;
                            width: 100%;
                            height: 0%;
                            pointer-events: none;
                            opacity: 0.11; /* agar tidak ganggu konten utama */
                            z-index: 0;
                            }

                            .watermark-row {
                            position: absolute;
                            }

                            .watermark-row img {
                            width: 45rem;
                            height: auto;
                            }

                            /* Baris 1 - kiri bawah */
                            .watermark-left {
                            bottom: 0;
                            left: -230px;
                            margin-bottom: -180px;
                            }

                            /* Baris 2 - tengah */
                            .watermark-center {
                            bottom: 10px; /* sedikit lebih tinggi dari baris bawah */
                            left: 30px;
                            transform: translateX(-50%);
                            }

                            /* Baris 3 - kanan atas */
                            .watermark-right {
                            bottom: 240px; /* di atas baris tengah */
                            right: -230px;
                            }

                            /* Rotasi zig-zag */
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
                    <div class="header">
                        <table>
                            <tr>
                                <td>
                                    <img src="'. public_path('assets/images/Invoice Permit_20251008_233910_0002.png') .'" class="logo" alt="Logo">
                                </td>
                                <td class="invoice-title">
                                    INVOICE
                                </td>
                            </tr>
                        </table>
                    </div>
                        <table class="invoice-info">
                            <tr>
                                <td class="number"><b> '. $record->invoice_number . '/NXN/INV/STARLITE-PRM/2025</b></td>
                                <td class="date">DATE: ' . $record->created_at->format('d/m/Y') . '</td>
                            </tr>
                        </table>
                    
                    <div class="to-section">
                        <table class="">
                            <tr>
                                <td class="left"><b> Kepada :</b></td>
                                <td class="right">No. PO :</td>
                            </tr>
                            <tr>
                                <td><b>' . $record->customer->customer_name . '</b> </td>
                            </tr>
                            <tr>
                                <td><b>' . $record->customer->address . '</b> </td>
                            </tr>
                            <tr>
                                <td>' . $record->customer->email . ' </td>
                            </tr>
                            <tr>
                                <td>' . $record->customer->phone . ' </td>
                            </tr>
                        </table>
                    </div>
                    <table>
                        <thead style="text-align:center;">
                            <tr >
                                <th>ITEM DESCRIPTION</th>
                                <th style="text-align:right;">QTY</th>
                                <th style="text-align:right;">PRICE</th>
                                <th style="text-align:right;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>';
                           foreach ($record->items as $index => $item) {
                                // $rowColor = $index % 2 === 0 ? '#f9f9f9' : '#eaeaea'; 
                                $rowColor = $index % 2 === 0 
                                        ? 'rgba(0, 0, 0, 0.03)'   
                                        : 'rgba(0, 0, 0, 0.07)';

                                    $html .= '<tr style="background-color: ' . $rowColor . ';">
                                                <td>' . $item->service->service_name . '</td>
                                                <td style="text-align:right;">' . $item->qty . '</td>
                                                <td style="text-align:right;">Rp ' . number_format($item->unit_price, 0, ',', '.') . '</td>
                                                <td style="text-align:right;">Rp ' . number_format($item->subtotal, 0, ',', '.') . '</td>
                                            </tr>';
                                }

            $html .= '</tbody>
                    </table>

                    <div class="total-box">
                         <table class="">
                            <tr>
                                <td class="left"><b>TOTAL</b></td>
                                 <td>Rp </td>
                                <td style="text-align:right;"><b>' . number_format($total, 0, ',', '.') . '</b></td>
                            </tr>
                            <tr>
                                 <td class="left"><b>DP 20%</b></td>
                                 <td>Rp </td>
                                <td style="text-align:right;"><b>' . number_format($dpteper, 0, ',', '.') . '</b> </td>
                            </tr>
                            
                            <tr>
                            
                                 <td class="left"><b>PPN (11%)</b></td>
                                 <td>Rp </td>
                                <td style="text-align:right;"><b>' . number_format($tax, 0, ',', '.') . ' </b></td>
                            </tr>
                            
                            <tr>
                            
                                 <td class="left"><b>TOTAL DP INC. PPN</b></td>
                                 <td>Rp </td>
                                <td style="text-align:right;"><b>' . number_format($totaldp, 0, ',', '.') . ' </b></td>
                            </tr>
                             <tr>
                            
                                 <td class="left"><b>TOTAL</b></td>
                                 <td>Rp </td>
                                <td style="text-align:right;"><b>' . number_format($grandTotal, 0, ',', '.') . ' </b></td>
                            </tr>
                        </table>
                        <table>
                        <tr>
                            <td colspan="3" style="text-align:right; font-style:italic; font-size:12px;">
                                (** ' . trim($spellNumber ($grandTotal)) . ' Rupiah **)
                            </td>
                            </tr>
                        </table>
                    </div>
                     <table class="total-box-footer">
                            <tr>
                                <td class="number"><b>BANK MANDIRI - 1180014213705 PT DAPOER POESAT NOESANTARA GROUP</b></td>
                            </tr>
                        </table>

                </body>
               
                </html>';

        $pdf = \PDF::loadHTML($html)->setPaper('A4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()), 
            "Invoice-{$record->id}.pdf"
        );
    }
}