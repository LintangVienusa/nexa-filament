<?php

namespace App\Console\Commands;

use App\Models\ODCDetail;
use App\Models\ODPDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\HomeConnectReport;
use App\Models\PoleDetail;
use App\Models\FeederDetail;
use Throwable;

class FixImageOrientation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-image-orientation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Mulai memperbaiki orientasi gambar...');

        HomeConnectReport::chunk(20, function ($reports) {
            foreach ($reports as $report) {
                $imageColumns = [
                    $report->foto_label_odp,
                    $report->foto_sn_ont,
                    $report->foto_label_id_plg,
                    $report->foto_qr,
                ];

                $this->get_filepath_and_rotate_image($imageColumns);
            }
        });

        PoleDetail::chunk(20, function ($poles) {
            foreach ($poles as $pole) {
                $imageColumns = [
                    $pole->digging,
                    $pole->instalasi,
                    $pole->coran,
                    $pole->tiang_berdiri,
                    $pole->labeling_tiang,
                    $pole->aksesoris_tiang
                ];

                $this->get_filepath_and_rotate_image($imageColumns);
            }
        });

        PoleDetail::chunk(20, function ($poles) {
            foreach ($poles as $pole) {
                $imageColumns = [
                    $pole->digging,
                    $pole->instalasi,
                    $pole->coran,
                    $pole->tiang_berdiri,
                    $pole->labeling_tiang,
                    $pole->aksesoris_tiang
                ];

                $this->get_filepath_and_rotate_image($imageColumns);
            }
        });

        ODCDetail::chunk(20, function ($odcs) {
            foreach ($odcs as $odc) {
                $imageColumns = [
                    $odc->instalasi,
                    $odc->power_optic_olt,
                    $odc->flexing_conduit,
                    $odc->odc_terbuka,
                    $odc->odc_tertutup,
                    $odc->closure
                ];

                $this->get_filepath_and_rotate_image($imageColumns);
            }
        });

        ODPDetail::chunk(20, function ($odps) {
            foreach ($odps as $odp) {
                $imageColumns = [
                    $odp->instalasi,
                    $odp->odp_terbuka,
                    $odp->odp_tertutup,
                    $odp->power_optic_odc
                ];

                $this->get_filepath_and_rotate_image($imageColumns);
            }
        });

        FeederDetail::chunk(20, function ($feeders) {
            foreach ($feeders as $feeder) {
                $imageColumns = [
                    $feeder->pulling_cable,
                    $feeder->pulling_cable_b,
                    $feeder->instalasi
                ];

                $this->get_filepath_and_rotate_image($imageColumns);
            }
        });

        $this->info('✅ Selesai! Semua gambar sudah diproses.');
        return Command::SUCCESS;
    }

    /**
     * @param array $imageColumns
     * @return void
     */
    public function get_filepath_and_rotate_image(array $imageColumns): void
    {
        foreach ($imageColumns as $path) {
            if (!$path) {
                continue;
            }

            if (!Storage::disk('public')->exists($path)) {
                continue;
            }

            try {
                $fullPath = Storage::disk('public')->path($path);

                $image = Image::make($fullPath);

                if (function_exists('exif_read_data')) {
                    $image->orientate();
                    $image->save(); // overwrite old file
                }

            } catch (Throwable $e) {
                logger()->error('Gagal memperbaiki orientasi gambar', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
