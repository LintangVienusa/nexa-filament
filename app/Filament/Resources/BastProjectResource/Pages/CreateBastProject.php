<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PoleDetail;
// use App\Models\CableDetail;s
use App\Models\FeederDetail;
use App\Models\ODCDetail;
use App\Models\ODPDetail;
use App\Models\HomeConnect;
use App\Models\MappingHomepass;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateBastProject extends CreateRecord
{
    protected bool $excelValid = true;
    protected static string $resource = BastProjectResource::class;

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

                $sheet = Excel::toArray([], $path)[0] ?? [];

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

                $sheet = Excel::toArray([], $path)[0] ?? [];

                $header = array_map('strtoupper', array_map('trim', $sheet[0] ?? []));
                $expectedHeaders = ['TIANG','ODP', 'ODC', 'FEEDER'];

                if (!in_array('TIANG', $header) || !in_array('ODP', $header) || !in_array('ODC', $header) || !in_array('FEEDER', $header)) {
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

            } catch (\Throwable $th) {
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


            if ($record->pass === 'HOMEPASS' && $record->list_pole) {

                 $path = storage_path('app/public/' . $record->list_pole);

                if (!file_exists($path)) return;

                $sheet = Excel::toArray([], $path)[0] ?? [];

                foreach ($sheet as $row) {
                    if (empty($row[0]) || strtoupper(trim($row[0]))=== 'NO_TIANG') continue;

                    PoleDetail::create([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'pole_sn' => trim($row[0]),
                    ]);

                    //  CableDetail::create([
                    //     'site' => $record->site,
                    //     'bast_id' => $record->bast_id,
                    //     'pole_sn' => trim($row[0]),
                    // ]);
                }

            }

             if ($record->pass === 'HOMEPASS' && $record->list_feeder_odc_odp) {

                 $path = storage_path('app/public/' . $record->list_feeder_odc_odp);

                if (!file_exists($path)) return;

                $sheet = Excel::toArray([], $path)[0] ?? [];

                foreach ($sheet as $row) {
                    if (empty($row[3]) || strtoupper(trim($row[3]))=== 'FEEDER') continue;

                    FeederDetail::updateOrCreate([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'feeder_name' => trim($row[3]),
                    ]);
                }

                foreach ($sheet as $row) {
                    if (empty($row[2]) || strtoupper(trim($row[2]))=== 'ODC') continue;

                    ODCDetail::updateOrCreate([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'feeder_name' => trim($row[3]),
                        'odc_name' => trim($row[2]),
                    ]);
                }

                foreach ($sheet as $row) {
                    if (empty($row[1]) || strtoupper(trim($row[1]))=== 'ODP') continue;

                    ODPDetail::updateOrCreate([
                        'site' => $record->site,
                        'bast_id' => $record->bast_id,
                        'odc_name' => trim($row[2]),
                        'odp_name' => trim($row[1]),
                    ]);

                    for ($i = 1; $i <= 8; $i++) {

                        HomeConnect::updateOrCreate(
                            [
                                'bast_id'   => $record->bast_id,
                                'odp_name'  => trim($row[2]),
                                'port_odp'  => $i, 
                            ],
                            [
                                'site'      => $record->site,
                                'status_port' => 'idle', 
                            ]
                        );
                    }
                }

                foreach ($sheet as $row) {
                    $feeder = trim($row[3] ?? '');
                    $odc    = trim($row[2] ?? '');
                    $odp    = trim($row[1] ?? '');
                    $pole    = trim($row[0] ?? '');

                    if ($feeder === '' || $odc === '' || $odp === '' || $pole === '') continue;
                    if (strtoupper($feeder) === 'FEEDER') continue;


                    MappingHomepass::updateOrCreate(
                         [
                            'pole'=> $pole,
                            'ODC' => $odc,
                            'ODP' => $odp,
                        ],
                        [
                            'province_name' => $record->province_name,
                            'regency_name'  => $record->regency_name,
                            'village_name'  => $record->village_name,
                            'station_name'  => $record->station_name,
                            'site'          => $record->site,
                            'feeder_name'   => $feeder,
                            'created_at'    => now(),
                        ],
                        [
                            'updated_at'    => now(),
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
                            'bast_id'        => $record->bast_id,
                            'id_pelanggan'   => trim($row[0] ?? ''),
                            'name_pelanggan' => trim($row[1] ?? ''),
                            'odp_name'       => trim($row[2] ?? ''),
                            'sn_ont'         => trim($row[3] ?? ''),
                        ]);

                        
                    }

                    Notification::make()
                        ->title('Data berhasil diimport dari Excel')
                        ->success()
                        ->send();
                }
            }elseif($record->pass === 'HOMECONNECT') {
                HomeConnect::create([
                    'bast_id' => $record->bast_id,
                ]);
            }
    }
}
