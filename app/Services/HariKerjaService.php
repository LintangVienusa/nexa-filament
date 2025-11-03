<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HariKerjaService
{
    /**
     * Ambil hari libur nasional dan cuti bersama dari API
     */
    protected function getLiburTahun($year): array
    {
        $response = Http::get("https://api-harilibur.vercel.app/api?year={$year}");
        if ($response->ok()) {
            $data = $response->json();
            return collect($data)->pluck('date')->toArray();
        }
        return [];
    }

    /**
     * Hitung hari kerja dari attendance, exclude weekend + hari libur nasional
     */
    public function hitungHariKerja($employeeId, $startDate, $endDate): array
    {
        $startDate = Carbon::now()->subMonth()->setDay(28)->startOfDay();
        $endDate   = Carbon::now()->setDay(27)->endOfDay();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $year = $start->format('Y');

        // Ambil tanggal libur nasional / cuti bersama
        $libur = $this->getLiburTahun($year);

        // Hitung jumlah hari kerja
        $period = CarbonPeriod::create($start, $end);
        $jumlahHariKerja = 0;

        foreach ($period as $date) {
            $wday = $date->dayOfWeekIso; // 1=Senin ... 7=Minggu
            if ($wday < 6 && !in_array($date->toDateString(), $libur)) {
                $jumlahHariKerja++;
            }
        }

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereIn('status', [0])
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $potonganAlpha = 0;
        $h = 0;

        foreach ($attendances as $att) {
            
            $h++;
        }
        $jml_alpha = $jumlahHariKerja-$h;

        return [
            'jumlah_hari_kerja' => $jumlahHariKerja,
            'jml_absensi' => $h,
            'jml_alpha' => $jml_alpha,
        ];
    }
}