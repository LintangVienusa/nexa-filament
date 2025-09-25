<?php

namespace App\Services;

class BpjsKesehatanService
{
    protected int $plafon = 12000000; 

    public function hitung(int $gajiPokok, int $tunjanganTetap = 0): array
    {
        $dasar = $gajiPokok + $tunjanganTetap;

        if ($dasar > $this->plafon) {
            $dasar = $this->plafon;
        }

        // total iuran 5%
        $total = $dasar * 0.05;

        return  [
            'dasar_perhitungan' => $dasar,
            'total_iuran'       => $total,
            'perusahaan'        => $dasar * 0.04,
            'karyawan'          => $dasar * 0.01,
        ];
    }
}