<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Attendance;

class ConvertBase64Attendance extends Command
{
     protected $signature = 'attendance:convert-base64';
    protected $description = 'Konversi base64 check_out_evidence menjadi file gambar terkompres tanpa Intervention Image';

    public function handle()
    {
        $this->info('🚀 Memulai proses konversi base64 ke file...');

        // Ambil data (ubah limit sesuai kebutuhan)
        $rows = Attendance::where('id', 11)->whereNotNull('check_out_evidence')->get();

        foreach ($rows as $row) {
            $checkInEvidence = $row->check_out_evidence;

            if (!Str::startsWith($checkInEvidence, 'data:image')) {
                $checkInEvidence = "data:image/png;base64," . $checkInEvidence;
            }

            $data = substr($checkInEvidence, strpos($checkInEvidence, ',') + 1);
            $decoded = base64_decode($data);

            if ($decoded === false) {
                $this->error("❌ Gagal decode base64 untuk ID: {$row->id}");
                continue;
            }

            $fileName = 'check_out_' . now()->format('Ymd_His') . '.jpg';
            $folder = storage_path('app/public/check_out_evidence');

            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $tempImage = imagecreatefromstring($decoded);
            if ($tempImage === false) {
                $this->error("❌ Gagal membuat image dari data untuk ID: {$row->id}");
                continue;
            }

            $width = imagesx($tempImage);
            $height = imagesy($tempImage);

            $maxWidth = 800;
            $maxHeight = 800;
            $ratio = min($maxWidth / $width, $maxHeight / $height, 1);

            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $savePath = $folder . '/' . $fileName;
            imagejpeg($compressedImage, $savePath, 75);

            imagedestroy($tempImage);
            imagedestroy($compressedImage);

            $relativePath = 'check_out_evidence/' . $fileName;

             DB::connection('mysql_employees')->table('Attendances') 
                ->where('id', $row->id)
                ->update(['check_out_evidence' => $relativePath]);

            $this->info("✅ Berhasil disimpan: {$savePath}");
        }

        $this->info('🎉 Selesai memproses semua data.');
        return 0;
    }
}
