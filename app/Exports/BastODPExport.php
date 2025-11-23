<?php

namespace App\Exports;

use App\Models\ODPDetail;
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
use Carbon\Carbon;

class BastODPExport implements WithEvents
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
                $details = ODPDetail::where('bast_id', $this->project->bast_id)
                    ->where('odp_name', $this->project->odp_name)
                    ->first();
                
                $bastproject = BastProject::where('bast_id', $this->project->bast_id)
                    ->first();
                $hariTanggal = Carbon::parse($bastproject->bast_date)
                        ->locale('id')
                        ->translatedFormat('l/Y-m-d');


                $sheet = $event->sheet->getDelegate()->setTitle('IMPLEMENTATION PHOTO ODP');;

                $logo = new Drawing();
                $logo->setName('Logo');
                $logo->setDescription('Logo Project');
                $logo->setPath(public_path('assets/images/Picture1.png')); 
                $logo->setCoordinates('C3');
                $logo->setHeight(80); 
                $logo->setOffsetX(5); 
                $logo->setOffsetY(5); 
                $logo->setWorksheet($sheet);

                $logo2 = new Drawing();
                $logo2->setName('Logo2');
                $logo2->setDescription('Logo Project2');
                $logo2->setPath(public_path('assets/images/Invoice Permit_20251008_233910_0002.png')); 
                $logo2->setCoordinates('O3');
                $logo2->setHeight(80); 
                $logo2->setOffsetX(5); 
                $logo2->setOffsetY(5); 
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
                    ['Lokasi Pekerjaan','', ': ' . $bastproject->village_name],
                    ['Stasiun','', ': ' . $bastproject->station_name ]
                ], null, 'C9', true);

                $sheet->fromArray([
                    [ 'Koordinat','', ': ' . $details->latitude.', '. $details->longitude],
                    ['Hari/ Tanggal','', ': ' . $hariTanggal, '', '']
                ], null, 'H9', true);

                $sheet->fromArray([
                    [  'Keterangan','', ': ' . $bastproject->notes],
                ], null, 'M9', true);

                $sheet->getStyle('C9:E10')->getFont()->setBold(true);
                $sheet->getStyle('H9:J10')->getFont()->setBold(true);
                $sheet->getStyle('M9:P9')->getFont()->setBold(true);
                

                $sheet->getStyle('E9:G9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                $sheet->getStyle('E10:G10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                $sheet->getStyle('M9:P9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                
                $sheet->getStyle('C12:F13')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('C12')->getFont()->setBold(true)->setSize(11);
                
                $sheet->getStyle('C12:F13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5EFCA');

                $sheet->mergeCells('C12:F13');
                $sheet->fromArray([
                    [ 'Instalasi '.$details->odp_name],
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
                                    
                                    
                                    $photoPath = public_path('storage/' . $details->instalasi);
                                    $sheet->setCellValue('C14', '');

                    if (file_exists($photoPath) && $details->instalasi !='' ) {

                        $colWidth = 0;
                        foreach (range($startCol, $endCol) as $col) {
                            $colWidth += $sheet->getColumnDimension($col)->getWidth() * 7;
                        }

                        $rowHeight = 0;
                        for ($r = $startRow; $r <= $endRow; $r++) {
                            $rowHeight += $sheet->getRowDimension($r)->getRowHeight() ?: 15;
                        }

                        
                        $imgWidth = 0;
                        $imgHeight = 0;
                        $scale = 1;

                        if (file_exists($photoPath)) {
                            $size = getimagesize($photoPath);
                            if ($size) {
                                [$imgWidth, $imgHeight] = $size;
                                if ($imgWidth > 0 && $imgHeight > 0) {
                                    $scale = min(($colWidth - 10) / $imgWidth, ($rowHeight - 10) / $imgHeight);
                                }
                            }
                        }

                        $drawing = new Drawing();
                        $drawing->setPath(public_path('storage/' . $details->instalasi));
                        $drawing->setCoordinates($startCol . $startRow);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
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
                
                $sheet->getStyle('C31')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('C31:F32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5EFCA');

                $sheet->mergeCells('C31:F32');
                $sheet->fromArray([
                    [ 'ODP Tertutup'],
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

                $range = 'C33:F49';
                    [$startCol, $startRow, $endCol, $endRow] = sscanf($range, "%[A-Z]%d:%[A-Z]%d");
                                   
                                    
                                    $photoPath = public_path('storage/' . $details->odp_tertutup);
                                    $sheet->setCellValue('C33', '');

                    if (file_exists($photoPath) && $details->odp_tertutup !='' ) {

                        $colWidth = 0;
                        foreach (range($startCol, $endCol) as $col) {
                            $colWidth += $sheet->getColumnDimension($col)->getWidth() * 7;
                        }

                        $rowHeight = 0;
                        for ($r = $startRow; $r <= $endRow; $r++) {
                            $rowHeight += $sheet->getRowDimension($r)->getRowHeight() ?: 15;
                        }

                        
                        $imgWidth = 0;
                        $imgHeight = 0;
                        $scale = 1;

                        if (file_exists($photoPath)) {
                            $size = getimagesize($photoPath);
                            if ($size) {
                                [$imgWidth, $imgHeight] = $size;
                                if ($imgWidth > 0 && $imgHeight > 0) {
                                    $scale = min(($colWidth - 10) / $imgWidth, ($rowHeight - 10) / $imgHeight);
                                }
                            }
                        }

                        $drawing = new Drawing();
                        $drawing->setPath(public_path('storage/' . $details->odp_tertutup));
                        $drawing->setCoordinates($startCol . $startRow);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        $drawing->setWidth(190);
                        $drawing->setHeight(190);
                        $drawing->setWorksheet($sheet);
                    }


                
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
                $sheet->getStyle('H12')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('H12:K13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5EFCA');

                $sheet->mergeCells('H12:K13');
                $sheet->fromArray([
                    [ 'Power Optic From ODC TO '.$details->odp_name],
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

                $range = 'H14:K30';
                    [$startCol, $startRow, $endCol, $endRow] = sscanf($range, "%[A-Z]%d:%[A-Z]%d");
                                    
                                    
                                    $photoPath = public_path('storage/' . $details->power_optic_odc);
                                    $sheet->setCellValue('H14', '');

                    if (file_exists($photoPath) && $details->power_optic_odc !='' ) {

                        $colWidth = 0;
                        foreach (range($startCol, $endCol) as $col) {
                            $colWidth += $sheet->getColumnDimension($col)->getWidth() * 7;
                        }

                        $rowHeight = 0;
                        for ($r = $startRow; $r <= $endRow; $r++) {
                            $rowHeight += $sheet->getRowDimension($r)->getRowHeight() ?: 15;
                        }

                        $imgWidth = 0;
                        $imgHeight = 0;
                        $scale = 1;

                        if (file_exists($photoPath)) {
                            $size = getimagesize($photoPath);
                            if ($size) {
                                [$imgWidth, $imgHeight] = $size;
                                if ($imgWidth > 0 && $imgHeight > 0) {
                                    $scale = min(($colWidth - 10) / $imgWidth, ($rowHeight - 10) / $imgHeight);
                                }
                            }
                        }

                        $drawing = new Drawing();
                        $drawing->setPath(public_path('storage/' . $details->power_optic_odc));
                        $drawing->setCoordinates($startCol . $startRow);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        $drawing->setWidth(190);
                        $drawing->setHeight(190);
                        $drawing->setWorksheet($sheet);
                    }


                $sheet->fromArray([
                    [ '' ],
                ], null, 'H14', true);

                

                
                

                $sheet->mergeCells('H52:K68');
                $sheet->fromArray([
                    [ '' ],
                ], null, 'H52', true);

                
                $sheet->getStyle('M12:P13')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('M12')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('M12:P13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5EFCA');

                $sheet->mergeCells('M12:P13');
                $sheet->fromArray([
                    [  'ODP Terbuka'],
                ], null, 'M12', true);

                $sheet->getStyle('M14:P30')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                $sheet->mergeCells('M14:P30');

                $range = 'M14:P30';
                    [$startCol, $startRow, $endCol, $endRow] = sscanf($range, "%[A-Z]%d:%[A-Z]%d");
                                    
                                    
                                    $photoPath = public_path('storage/' . $details->odp_terbuka);
                                    $sheet->setCellValue('M14', '');

                    if (file_exists($photoPath) && $details->odp_terbuka !='' ) {

                        $colWidth = 0;
                        foreach (range($startCol, $endCol) as $col) {
                            $colWidth += $sheet->getColumnDimension($col)->getWidth() * 7;
                        }

                        $rowHeight = 0;
                        for ($r = $startRow; $r <= $endRow; $r++) {
                            $rowHeight += $sheet->getRowDimension($r)->getRowHeight() ?: 15;
                        }

                        
                        $imgWidth = 0;
                        $imgHeight = 0;
                        $scale = 1;

                        if (file_exists($photoPath)) {
                            $size = getimagesize($photoPath);
                            if ($size) {
                                [$imgWidth, $imgHeight] = $size;
                                if ($imgWidth > 0 && $imgHeight > 0) {
                                    $scale = min(($colWidth - 10) / $imgWidth, ($rowHeight - 10) / $imgHeight);
                                }
                            }
                        }

                        $drawing = new Drawing();
                        $drawing->setPath(public_path('storage/' . $details->odp_terbuka));
                        $drawing->setCoordinates($startCol . $startRow);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        $drawing->setWidth(190);
                        $drawing->setHeight(190);
                        $drawing->setWorksheet($sheet);
                    }

                $sheet->fromArray([
                    [ '' ],
                ], null, 'M14', true);
                
                




                $sheet->getStyle('C71:I72')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('C71')->getFont()->setBold(true)->setSize(11);

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

                $sheet->getStyle('K71:P72')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $sheet->getStyle('K71')->getFont()->setBold(true)->setSize(11);

                $sheet->mergeCells('K71:P72');
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

                $sheet->getStyle('K77')->getFont()->setBold(true)->setSize(11);
                $sheet->mergeCells('K77:M78');
                $sheet->fromArray([
                    [ '(                                            )' ],
                ], null, 'K77', true);

                $sheet->getStyle('N73:P78')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                $sheet->getStyle('N77:P78')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('N77')->getFont()->setBold(true)->setSize(11);
                $sheet->mergeCells('N77:P78');
                $sheet->fromArray([
                    [ '(                                             )' ],
                ], null, 'N77', true);

               
            }
        ];
    }
}
