<?php

namespace App\Services;

class BpjsKetenagakerjaanService
{
    protected $maxJP = 9450000; // Batas gaji untuk Jaminan Pensiun (JP)

    public function hitung($basicSalary)
    {
        // Jaminan Pensiun (JP)
        $dasarJP      = min($basicSalary, $this->maxJP);
        $jpPerusahaan = $dasarJP * 0.02; // 2% ditanggung perusahaan
        $jpKaryawan   = $dasarJP * 0.01; // 1% ditanggung karyawan

        // Jaminan Hari Tua (JHT)
        $jhtPerusahaan = $basicSalary * 0.037; // 3.7% perusahaan
        $jhtKaryawan   = $basicSalary * 0.02;  // 2% karyawan

        // Jaminan Kecelakaan Kerja (JKK) → asumsi 0.24% (tingkat risiko rendah)
        $jkkPerusahaan = $basicSalary * 0.0024;

        // Jaminan Kematian (JKM) → 0.3% perusahaan
        $jkmPerusahaan = $basicSalary * 0.003;

        return [
            'jp_company'  => $jpPerusahaan,
            'jp_employee'    => $jpKaryawan,
            'jht_company' => $jhtPerusahaan,
            'jht_employee'   => $jhtKaryawan,
            'jkk_company' => $jkkPerusahaan,
            'jkm_company' => $jkmPerusahaan,
        ];
    }
}