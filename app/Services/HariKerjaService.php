<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Leave;
use App\Models\Holiday;

class HariKerjaService
{
    /**
     * Ambil hari libur nasional dan cuti bersama dari API
     */
    protected function getLiburTahun($year): array
    {
        // $response = Http::withOptions([
        //                 'curl' => [
        //                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //                 ],
        //             ])->get('https://api-harilibur.vercel.app/api?year={$year}');
        // // $response = Http::get("https://api-harilibur.vercel.app/api?year={$year}");
        // if ($response->ok()) {
        //     $data = $response->json();
        //     return collect($data)->pluck('date')->toArray();
        // }
        // return [];

         return Holiday::where('year', $year)
                ->pluck('holiday_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->toArray();
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

       $leaves = Leave::where('employee_id', $employeeId)
            ->whereIn('status', [2]) // APPROVED
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        

        $potonganAlpha = 0;
        $h = 0;
        

        foreach ($attendances as $att) {
            
            $h++;
        }
        $jumlahLeave = 0;

        foreach ($leaves as $leave) {

            $leavePeriod = CarbonPeriod::create(
                Carbon::parse($leave->start_date),
                Carbon::parse($leave->end_date)
            );

            foreach ($leavePeriod as $tanggalCuti) {

                $wday = $tanggalCuti->dayOfWeekIso;

                // Bukan weekend
                if ($wday < 6) {
                    // Bukan hari libur nasional
                    if (!in_array($tanggalCuti->toDateString(), $libur)) {
                        $jumlahLeave++;
                    }
                }
            }
        }


        $jml_alpha = $jumlahHariKerja- ($h + $jumlahLeave);
        $jml_absensi = $h + $jumlahLeave;

        return [
            'jumlah_hari_kerja' => $jumlahHariKerja,
            'jml_absensi' => $jml_absensi,
            // 'jml_leave'      => $jumlahLeave,
            'jml_alpha' => $jml_alpha,
        ];
    }
}