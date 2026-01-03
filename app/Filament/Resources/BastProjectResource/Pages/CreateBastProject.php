<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PoleDetail;
use App\Models\PurchaseOrder;
use App\Models\FeederDetail;
use App\Models\ODCDetail;
use App\Models\ODPDetail;
use App\Models\HomeConnect;
use App\Models\MappingHomepass;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use App\Services\FormulaValueBinderService;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class CreateBastProject extends CreateRecord
{
    protected static string $resource = BastProjectResource::class;
    protected bool $excelValid = true;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if ($data['pass'] === 'HOMEPASS') {

            if (!empty($data['list_pole'])) {
                $path = storage_path('app/public/' . $data['list_pole']);

                if (!Storage::disk('public')->exists($data['list_pole'])) {
                    Notification::make()
                        ->title('File Tiang tidak ditemukan')
                        ->body('Pastikan file Excel Tiang sudah diupload.')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'list_pole' => 'File Tiang tidak ditemukan.',
                    ]);
                }

                $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];


                if (empty($sheet) || empty($sheet[0][0]) || strtoupper(trim($sheet[0][0])) !== 'NO_TIANG') {
                    Notification::make()
                        ->title('Format Excel Tiang salah')
                        ->body('Pastikan kolom pertama berjudul NO_TIANG.')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'list_pole' => 'Format Excel Tiang salah.',
                    ]);
                }


            }

            if (!empty($data['list_feeder_odc_odp'])) {
                $path = storage_path('app/public/' . $data['list_feeder_odc_odp']);

                if (!Storage::disk('public')->exists($data['list_feeder_odc_odp'])) {
                    Notification::make()
                        ->title('File Tiang/Feeder/ODC/ODP tidak ditemukan')
                        ->body('Pastikan file Excel Tiang/Feeder/ODC/ODP sudah diupload.')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'list_feeder_odc_odp' => 'File Tiang/Feeder/ODC/ODP tidak ditemukan.',
                    ]);
                }

                $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];

                $header = array_map('strtoupper', array_map('trim', $sheet[0] ?? []));
                $expectedHeaders = ['TIANG', 'ODP', 'ODC', 'FEEDER'];

                if (!in_array('TIANG', $header) && !in_array('ODP', $header) && !in_array('ODC', $header) && !in_array('FEEDER', $header)) {
                    Notification::make()
                        ->title('Format Excel Tiang/Feeder/ODC/ODP salah')
                        ->body('Pastikan file memiliki kolom ODP, ODC, dan FEEDER.')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'list_feeder_odc_odp' => 'Format Excel Tiang/Feeder/ODC/ODP salah.',
                    ]);
                }
            }
        }

        if ($data['pass'] === 'HOMECONNECT' && !empty($data['list_homeconnect'])) {
            $path = storage_path('app/public/' . $data['list_homeconnect']);

            if (!Storage::disk('public')->exists($data['list_homeconnect'])) {
                Notification::make()
                    ->title('File tidak ditemukan')
                    ->body('Pastikan file berhasil diupload sebelum menyimpan.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'list_homeconnect' => 'File tidak ditemukan atau belum terupload.',
                ]);
            }

            try {
                $sheet = Excel::toArray([], $path)[0] ?? [];

                if (empty($sheet) || count($sheet[0]) < 4) {
                    Notification::make()
                        ->title('Format Excel tidak valid')
                        ->body('File Excel kosong atau kolom tidak lengkap.')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'list_homeconnect' => 'Format Excel tidak valid.',
                    ]);
                }

                $header = array_map('strtoupper', array_map('trim', $sheet[0]));
                $expectedHeader = ['ID PELANGGAN', 'NAMA PELANGGAN', 'ODP', 'ONT'];

                if ($header !== $expectedHeader) {
                    Notification::make()
                        ->title('Format Excel Salah')
                        ->body('Pastikan urutan kolom: ID PELANGGAN, NAMA PELANGGAN, ODP, ONT')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'list_homeconnect' => 'Format header Excel salah.',
                    ]);
                }

            } catch (Throwable $th) {
                Notification::make()
                    ->title('Gagal membaca file')
                    ->body('Pastikan file Excel tidak rusak.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'list_homeconnect' => 'Gagal membaca file Excel.',
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function afterCreate(): void
    {
        if ($this->excelValid === false) {
            return;
        }

        $record = $this->record;

        $purchaseOrder = PurchaseOrder::where('po_number', $record->po_number)->first();
        $totaltarget = $purchaseOrder?->total_target ?? null;
        if ($totaltarget == 512) {
            $jpole = 101;
            $jodp = 65;
            $jodc = 17;
        } elseif ($totaltarget == 5120) {
            $jpole = 1001;
            $jodp = 641;
            $jodc = 161;
        } else {
            $jpole = $totaltarget+1;
            $jodp = $totaltarget+1;
            $jodc = $totaltarget+1;
        }


        if ($record->pass === 'HOMEPASS' && $record->list_pole) {

            $path = storage_path('app/public/' . $record->list_pole);

            if (!file_exists($path)) return;

            // $sheet = Excel::toArray([], $path)[0] ?? [];
            $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];
            $spreadsheet = IOFactory::load($path);
            $sheetExcel = $spreadsheet->getActiveSheet();
            $startNumber = 1;
            $counter = 0;
            $validPoleList = [];

            // for ($i = 1; $i <= $sheetExcel->getHighestRow(); $i++) {
            for ($i = 1; $i <= $jpole; $i++) {
                $cell = $sheetExcel->getCell('A' . $i);

                $poleSn = $cell->getCalculatedValue();
                // foreach ($sheet as $index => $row) {
                if (empty($poleSn) || strtoupper(trim($poleSn)) === 'NO_TIANG') continue;


                PoleDetail::create([
                    'site' => $record->site,
                    'bast_id' => $record->bast_id,
                    'pole_sn' => $poleSn,
                ]);
            }

        }

        if ($record->pass === 'HOMEPASS' && $record->list_feeder_odc_odp) {

            $path = storage_path('app/public/' . $record->list_feeder_odc_odp);

            if (!file_exists($path)) return;
            $nodc = 1;
            $nodp = 1;
            // $sheet = Excel::toArray([], $path)[0] ?? [];

            $sheet = Excel::toArray(new FormulaValueBinderService, $path)[0] ?? [];

            $spreadsheet = IOFactory::load($path);
            $sheetExcel = $spreadsheet->getActiveSheet();

            $validPoleList = [];

            for ($rowIndex = 2; $rowIndex <= $sheetExcel->getHighestRow(); $rowIndex++) {
                $cellp = $sheetExcel->getCell('A' . $rowIndex);
                $cellodp = $sheetExcel->getCell('B' . $rowIndex);
                $cellodc = $sheetExcel->getCell('C' . $rowIndex);
                $cellf = $sheetExcel->getCell('D' . $rowIndex);

                $pole = trim($cellp->getCalculatedValue());
                $odp = trim($cellodp->getCalculatedValue());
                $odc = trim($cellodc->getCalculatedValue());
                $feeder = trim($cellf->getCalculatedValue());

                if ($feeder === '' && $odc === '' && $odp === '' && $pole === '') continue;
                if (strtoupper($feeder) === 'FEEDER' || strtoupper($odc) === 'ODC' || strtoupper($odp) === 'ODP') continue;

                $validPoleList[] = $pole;

                // Update or create Feeder
                if ($feeder !== '') {
                    FeederDetail::updateOrCreate([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'feeder_name' => $feeder,
                    ]);
                }

                // Update or create ODC
                if ($odc !== '' && $nodc <= $jodc) {
                    ODCDetail::updateOrCreate([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'feeder_name' => $feeder,
                        'odc_name' => $odc,
                    ]);
                    $nodc++;
                }

                // Update or create ODP
                if ($odp !== '' && $nodp <= $jodc) {
                    ODPDetail::updateOrCreate([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'odc_name' => $odc,
                        'odp_name' => $odp,
                    ]);

                    $nodp++;

                    // Buat 8 port HomeConnect
                    for ($portIndex = 1; $portIndex <= 8; $portIndex++) {

                        $existing = HomeConnect::where('odp_name', $odp)
                            ->where('port_odp', $portIndex)
                            ->first();
                        if (!$existing) {
                            HomeConnect::updateOrCreate(
                                [
                                    'bast_id' => $record->bast_id,
                                    'po_number' => $record->po_number,
                                    'odp_name' => $odp,
                                    'port_odp' => $portIndex,
                                ],
                                [
                                    'site' => $record->site,
                                    'status_port' => 'idle',
                                ]
                            );
                        } elseif ($existing->status_port === 'used') {
                            // do nothing
                        } elseif ($existing->status_port === 'idle') {
                            $existing = HomeConnect::updateOrCreate(
                                [
                                    'odp_name' => $odp,
                                    'port_odp' => $portIndex,
                                ],
                                [
                                    'bast_id' => $record->bast_id,
                                    'po_number' => $record->po_number,
                                    'site' => $record->site,
                                    'status_port' => 'idle',
                                ]
                            );
                        }
                    }
                }

                // MappingHomepass
                MappingHomepass::updateOrCreate(
                    [
                        'pole' => $pole,
                        'ODC' => $odc,
                        'ODP' => $odp,
                    ],
                    [
                        'province_name' => $record->province_name,
                        'regency_name' => $record->regency_name,
                        'village_name' => $record->village_name,
                        'station_name' => $record->station_name,
                        'site' => $record->site,
                        'feeder_name' => $feeder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

        }

        if ($record->pass === 'HOMECONNECT' && !empty($record->list_homeconnect)) {

            $path = storage_path('app/public/' . $record->list_homeconnect);

            if (file_exists($path)) {
                $sheet = Excel::toArray([], $path)[0] ?? [];

                foreach ($sheet as $index => $row) {
                    if ($index === 0 || empty($row[0])) continue;


                    HomeConnect::create([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'po_number' => $record->po_number,
                        'id_pelanggan' => trim($row[0] ?? ''),
                        'name_pelanggan' => trim($row[1] ?? ''),
                        'odp_name' => trim($row[2] ?? ''),
                        'sn_ont' => trim($row[3] ?? ''),
                    ]);


                }

                Notification::make()
                    ->title('Data berhasil diimport dari Excel')
                    ->success()
                    ->send();
            }
        } elseif ($record->pass === 'HOMECONNECT') {
            HomeConnect::create([
                'bast_id' => $record->bast_id,
            ]);
        }
    }
}
