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
                $sheet = $event->sheet->getDelegate();

                // === HEADER ===
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'IMPLEMENTATION PHOTO');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // === Info Proyek ===
                $sheet->fromArray([
                    ['Lokasi Pekerjaan', ': ' . $this->project->lokasi, 'Koordinat', ': ' . $this->project->koordinat, 'Keterangan', ': ' . $this->project->keterangan],
                    ['Stasiun', ': ' . $this->project->stasiun, 'Hari/ Tanggal', ': ' . $this->project->tanggal, '', '']
                ], null, 'A3', true);

                // === Warna highlight Jawa Barat ===
                $sheet->getStyle('B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                // === Tabel Header Box ===
                $headers = [
                    ['T001 Digging Hole', 'T001 Measuring Hole', 'T001 Solid Fill'],
                    ['T001 Pole Installation', 'T001 Pole Casting', 'T001 Pole Accessories Installation'],
                    ['T001 Cable Pulling', 'T001 ODC Installation', 'T001 ODP Integration']
                ];

                $startRow = 6;
                foreach ($headers as $rowIndex => $cols) {
                    $rowTop = $startRow + ($rowIndex * 12); // setiap blok 12 row
                    foreach ($cols as $colIndex => $title) {
                        $colLetter = chr(65 + ($colIndex * 3)); // A, D, G
                        $mergeRange = "{$colLetter}{$rowTop}:" . chr(ord($colLetter) + 2) . ($rowTop);
                        $sheet->mergeCells($mergeRange);
                        $sheet->setCellValue($colLetter . $rowTop, $title);

                        $sheet->getStyle($mergeRange)->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'C6E0B4']
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                            ]
                        ]);

                        // === Kotak foto ===
                        $photoPath = storage_path("app/public/photos/{$this->project->kode}/" . str_replace(' ', '_', $title) . ".jpg");
                        $imageTop = $rowTop + 1;
                        $imageBottom = $rowTop + 10;
                        $photoRange = "{$colLetter}{$imageTop}:" . chr(ord($colLetter) + 2) . "{$imageBottom}";
                        $sheet->mergeCells($photoRange);
                        $sheet->getStyle($photoRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                        if (file_exists($photoPath)) {
                            $drawing = new Drawing();
                            $drawing->setPath($photoPath);
                            $drawing->setCoordinates($colLetter . ($imageTop));
                            $drawing->setHeight(150);
                            $drawing->setOffsetX(10);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);
                        }
                    }
                }

                // === Bagian tanda tangan ===
                $footerStart = $startRow + (count($headers) * 12) + 2;
                $sheet->mergeCells("A{$footerStart}:C{$footerStart}");
                $sheet->mergeCells("D{$footerStart}:F{$footerStart}");
                $sheet->setCellValue("A{$footerStart}", 'PT DAPOER POESAT NOESANTARA GROUP');
                $sheet->setCellValue("D{$footerStart}", 'PT. Integrasi Jaringan Ekosistem (WEAVE)');
                $sheet->getStyle("A{$footerStart}:F{$footerStart}")->getAlignment()->setHorizontal('center')->setVertical('center');

                $footerSignRow = $footerStart + 4;
                $sheet->mergeCells("A{$footerSignRow}:C{$footerSignRow}");
                $sheet->mergeCells("D{$footerSignRow}:F{$footerSignRow}");
                $sheet->setCellValue("A{$footerSignRow}", "( {$this->project->nama_pelaksana} )");
                $sheet->setCellValue("D{$footerSignRow}", "( {$this->project->nama_pengawas} )");
                $sheet->getStyle("A{$footerSignRow}:F{$footerSignRow}")->getAlignment()->setHorizontal('center');
            }
        ];
    }
}
