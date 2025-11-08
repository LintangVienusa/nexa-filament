<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PoleDetail;
use App\Models\FeederDetail;
use App\Models\ODCDetail;
use App\Models\ODPDetail;

class CreateBastProject extends CreateRecord
{
    protected static string $resource = BastProjectResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

   protected function afterCreate(): void
    {

         $record = $this->record;


            if ($record->pass === 'HOMEPASS' && $record->list_pole) {

                 $path = storage_path('app/public/' . $record->list_pole);

                if (!file_exists($path)) return;

                $sheet = Excel::toArray([], $path)[0] ?? [];

                foreach ($sheet as $row) {
                    if (empty($row[0]) || trim($row[0])=== 'no_tiang') continue;

                    PoleDetail::create([
                        'bast_id' => $record->bast_id,
                        'pole_sn' => trim($row[0]),
                    ]);
                }

            }

             if ($record->pass === 'HOMEPASS' && $record->list_feeder_odc_odp) {

                 $path = storage_path('app/public/' . $record->list_feeder_odc_odp);

                if (!file_exists($path)) return;

                $sheet = Excel::toArray([], $path)[0] ?? [];

                foreach ($sheet as $row) {
                    if (empty($row[3]) || trim($row[3])=== 'FEEDER') continue;

                    FeederDetail::create([
                        'bast_id' => $record->bast_id,
                        'feeder_name' => trim($row[3]),
                    ]);
                }

                foreach ($sheet as $row) {
                    if (empty($row[2]) || trim($row[2])=== 'ODC') continue;

                    ODCDetail::create([
                        'bast_id' => $record->bast_id,
                        'feeder_name' => trim($row[3]),
                        'odc_name' => trim($row[2]),
                    ]);
                }

                foreach ($sheet as $row) {
                    if (empty($row[1]) || trim($row[1])=== 'ODP') continue;

                    ODPDetail::create([
                        'bast_id' => $record->bast_id,
                        'odc_name' => trim($row[2]),
                        'odp_name' => trim($row[1]),
                    ]);
                }

            }
    }
}
