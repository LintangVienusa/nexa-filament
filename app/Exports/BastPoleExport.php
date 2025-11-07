<?php

namespace App\Exports;

use App\Models\BastProject;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\PoleDetail;

class BastPoleExport implements WithEvents
{
    protected $project;

    public function __construct($project)
    {
        $this->project = $project;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $project = $this->project;
                $details = PoleDetail::where('bast_id', $this->project->bast_id)->first();


                $sheet = $event->sheet->getDelegate();

                $logo = new Drawing();
                $logo->setName('Logo');
                $logo->setDescription('Logo Project');
                $logo->setPath(public_path('assets/images/Picture1.png')); // ubah sesuai lokasi file kamu
                $logo->setCoordinates('C3');
                $logo->setHeight(80); // tinggi gambar dalam pixel
                $logo->setOffsetX(5); // jarak dari kiri
                $logo->setOffsetY(5); // jarak dari atas
                $logo->setWorksheet($sheet);

                $logo2 = new Drawing();
                $logo2->setName('Logo2');
                $logo2->setDescription('Logo Project2');
                $logo2->setPath(public_path('assets/images/Invoice Permit_20251008_233910_0002.png')); // ubah sesuai lokasi file kamu
                $logo2->setCoordinates('O3');
                $logo2->setHeight(80); // tinggi gambar dalam pixel
                $logo2->setOffsetX(5); // jarak dari kiri
                $logo2->setOffsetY(5); // jarak dari atas
                $logo2->setWorksheet($sheet);

                // === HEADER ===
                
                $sheet->getColumnDimension('A')->setWidth(2);
                $sheet->getColumnDimension('B')->setWidth(4);
                $sheet->mergeCells('F5:N6');
                $sheet->setCellValue('F5', 'IMPLEMENTATION PHOTO');
                $sheet->getStyle('F5')->getFont()
                        ->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE)->setBold(true)->setSize(26);
                $sheet->getStyle('F5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->setShowGridlines(false);

                $sheet->getStyle('B2:R78')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // === Info Proyek ===
                $sheet->fromArray([
                    ['Lokasi Pekerjaan','', ': ' . $this->project->village_name],
                    ['Stasiun','', ': ' . $this->project->village_name ]
                ], null, 'C9', true);

                $sheet->fromArray([
                    [ 'Koordinat','', ': ' . $details->latitude.', '. $details->longitude],
                    ['Hari/ Tanggal','', ': ' . $this->project->bast_date, '', '']
                ], null, 'H9', true);

                $sheet->fromArray([
                    [  'Keterangan','', ': ' . $this->project->notes],
                ], null, 'M9', true);

                // === Warna highlight Jawa Barat ===
                $sheet->getStyle('E9:G9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                $sheet->getStyle('E10:G10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                // === Tabel Header Box ===
                // $headers = [
                //     [$details->pole_sn.' Digging Hole', $details->pole_sn.' Measuring Hole', $details->pole_sn.' Solid Fill'],
                //     [$details->pole_sn.' Pole Installation', $details->pole_sn.' Pole Casting', $details->pole_sn.' Pole Accessories Installation'],
                //     [$details->pole_sn.' Cable Pulling', $details->pole_sn.' ODC Installation', $details->pole_sn.' ODP Integration']
                // ];

                $sheet->getStyle('C12:F13')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('C12')->getFont()->setBold(true)->setSize(14);
                
                $sheet->getStyle('C12:F13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                $sheet->mergeCells('C12:F13');
                $sheet->fromArray([
                    [  $details->pole_sn.' Digging Hole'],
                ], null, 'C12', true);

                $sheet->getStyle('C14:F30')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                $sheet->mergeCells('C14:F30');
                
                    $range = 'C14:F30';
                    [$startCol, $startRow, $endCol, $endRow] = sscanf($range, "%[A-Z]%d:%[A-Z]%d");
                                    // $sheet->fromArray([
                                    //     [ '' ],
                                    // ], null, 'C14', true);
                                    
                                    $photoPath = public_path('storage/' . $details->digging);
                                    $sheet->setCellValue('C14', '');

                                    if (file_exists($photoPath)) {

                        // Hitung lebar & tinggi area merge dalam pixel (approx)
                        $colWidth = 0;
                        foreach (range($startCol, $endCol) as $col) {
                            $colWidth += $sheet->getColumnDimension($col)->getWidth() * 7; // 1 kolom ≈ 7px
                        }

                        $rowHeight = 0;
                        for ($r = $startRow; $r <= $endRow; $r++) {
                            $rowHeight += $sheet->getRowDimension($r)->getRowHeight() ?: 15;
                        }

                        // Ambil ukuran asli gambar
                        [$imgWidth, $imgHeight] = getimagesize($photoPath);

                        // Skala agar gambar proporsional dalam kotak
                        $scale = min(($colWidth - 10) / $imgWidth, ($rowHeight - 10) / $imgHeight);

                        // === Tambahkan gambar ke Excel ===
                        $drawing = new Drawing();
                        $drawing->setPath(public_path('storage/' . $details->digging));
                        $drawing->setCoordinates($startCol . $startRow);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        // $drawing->setWidth($imgWidth * $scale);
                        // $drawing->setHeight($imgHeight * $scale);
                        $drawing->setWidth(190);
                        $drawing->setHeight(190);
                        $drawing->setWorksheet($sheet);
                    }


                $sheet->getStyle('C31:F32')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('C31')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('C31:F32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                $sheet->mergeCells('C31:F32');
                $sheet->fromArray([
                    [  $details->pole_sn.' Pole Installation'],
                ], null, 'C31', true);
                
                 $sheet->getStyle('C33:F49')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->mergeCells('C33:F49');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'C33', true);

                $sheet->getStyle('C50:F51')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('C50')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('C50:F51')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                
                $sheet->mergeCells('C50:F51');
                $sheet->fromArray([
                    [  $details->pole_sn.' Cable Pulling'],
                ], null, 'C50', true);

                 $sheet->getStyle('C52:F68')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                $sheet->mergeCells('C52:F68');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'C52', true);

                
                $sheet->getStyle('H12:K13')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle('H12')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('H12:K13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                $sheet->mergeCells('H12:K13');
                $sheet->fromArray([
                    [  $details->pole_sn.' Measuring Hole'],
                ], null, 'H12', true);

                $sheet->getStyle('H14:K30')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                

                $sheet->mergeCells('H14:K30');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'H14', true);

                $sheet->getStyle('H31:K32')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('H31')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('H31:K32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                

                $sheet->mergeCells('H31:K32');
                $sheet->fromArray([
                    [  $details->pole_sn.' Pole Casting'],
                ], null, 'H31', true);
                

                $sheet->getStyle('H33:K49')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->mergeCells('H33:K49');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'H33', true);

                
                $sheet->getStyle('H50:K51')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('H50')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('H50:K51')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                

                $sheet->mergeCells('H50:K51');
                $sheet->fromArray([
                    [  $details->pole_sn.' ODC Installation'],
                ], null, 'H50', true);
                

                $sheet->getStyle('H52:K68')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->mergeCells('H52:K68');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'H52', true);

                
                $sheet->getStyle('M12:Q13')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('M12')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('M12:Q13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                $sheet->mergeCells('M12:Q13');
                $sheet->fromArray([
                    [  $details->pole_sn.' Solid Fill'],
                ], null, 'M12', true);

                $sheet->getStyle('M14:Q30')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                $sheet->mergeCells('M14:Q30');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'M14', true);
                
                $sheet->getStyle('M31:Q32')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('M31')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('M31:Q32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                $sheet->mergeCells('M31:Q32');
                $sheet->fromArray([
                    [  $details->pole_sn.' Pole Accessories Installation'],
                ], null, 'M31', true);

                 $sheet->getStyle('M33:Q49')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                $sheet->mergeCells('M33:Q49');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'M33', true);

                $sheet->getStyle('M50:Q51')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('M50')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('M50:Q51')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAF2D0');

                $sheet->mergeCells('M50:Q51');
                $sheet->fromArray([
                    [  $details->pole_sn.' ODP Integration'],
                ], null, 'M50', true);

                $sheet->getStyle('M52:Q68')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                $sheet->mergeCells('M52:Q68');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'M52', true);


                // === Bagian tanda tangan ===

                $sheet->getStyle('C71:I72')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('C71')->getFont()->setBold(true)->setSize(14);

                $sheet->mergeCells('C71:I72');
                $sheet->fromArray([
                    [  'PT DAPOER POESAT NOESANTARA GROUP'],
                ], null, 'C71', true);

                $sheet->getStyle('C73:I78')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->mergeCells('C73:I78');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'C73', true);

                $sheet->getStyle('K71:Q72')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('K71')->getFont()->setBold(true)->setSize(14);

                $sheet->mergeCells('K71:Q72');
                $sheet->fromArray([
                    [  'PT. Integrasi Jaringan Ekosistem (WEAVE)'],
                ], null, 'K71', true);

                $sheet->getStyle('K73:M78')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->getStyle('K77:M78')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('K77')->getFont()->setBold(true)->setSize(14);
                $sheet->mergeCells('K77:M78');
                $sheet->fromArray([
                    [ '(                                            )' ],
                ], null, 'K77', true);

                $sheet->getStyle('N73:Q78')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->getStyle('N77:Q78')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('N77')->getFont()->setBold(true)->setSize(14);
                $sheet->mergeCells('N77:Q78');
                $sheet->fromArray([
                    [ '(                                             )' ],
                ], null, 'N77', true);

                // $footerStart = 71;
                // $sheet->mergeCells("A{$footerStart}:C{$footerStart}");
                // $sheet->mergeCells("D{$footerStart}:F{$footerStart}");
                // $sheet->setCellValue("A{$footerStart}", 'PT DAPOER POESAT NOESANTARA GROUP');
                // $sheet->setCellValue("D{$footerStart}", 'PT. Integrasi Jaringan Ekosistem (WEAVE)');
                // $sheet->getStyle("A{$footerStart}:F{$footerStart}")->getAlignment()->setHorizontal('center')->setVertical('center');

                // $footerSignRow = $footerStart + 4;
                // $sheet->mergeCells("A{$footerSignRow}:C{$footerSignRow}");
                // $sheet->mergeCells("D{$footerSignRow}:F{$footerSignRow}");
                // $sheet->setCellValue("A{$footerSignRow}", "( {$this->project->nama_pelaksana} )");
                // $sheet->setCellValue("D{$footerSignRow}", "( {$this->project->nama_pengawas} )");
                // $sheet->getStyle("A{$footerSignRow}:F{$footerSignRow}")->getAlignment()->setHorizontal('center');
            }
        ];
    }
}
