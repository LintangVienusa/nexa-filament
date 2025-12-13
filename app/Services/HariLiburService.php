<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HariLiburService
{
    public function getLiburTahun($startDate, $endDate)
    {
        // $response = Http::get("https://api-harilibur.vercel.app/api?year={$year}");
        // $response = Http::withOptions([
        //                 'curl' => [
        //                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //                 ],
        //             ])->get('https://api-harilibur.vercel.app/api?year={$year}');

        // if ($response->ok()) {
        //     $data = $response->json();
        //     return collect($data)->pluck('date')->toArray();
        // }

        // return [];
         return Holiday::whereBetween('holiday_date', [
                    Carbon::parse($startDate)->toDateString(),
                    Carbon::parse($endDate)->toDateString(),
                ])
                ->pluck('holiday_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->toArray();
    }

    public function hitungHariCuti($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();
        $year  = $start->format('Y');

        // Ambil hari libur nasional
        // $libur = $this->getLiburTahun($year);
        $libur = $this->getLiburTahun($startDate, $endDate);

        $period = CarbonPeriod::create($start, $end);
        $hariCuti = 0;

        foreach ($period as $date) {
            $wday = $date->dayOfWeekIso; // 1=Senin ... 7=Minggu

            // Jika bukan Sabtu/Minggu dan bukan libur → hitung 1 hari cuti
            if ($wday < 6 && !in_array($date->toDateString(), $libur)) {
                $hariCuti++;
            }
        }

        return $hariCuti;
    }
}