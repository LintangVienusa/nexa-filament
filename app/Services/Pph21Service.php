<?php

namespace App\Services;

class Pph21Service
{
    // PTKP (Penghasilan Tidak Kena Pajak) tahunan
    protected int $ptkpSingle = 54000000;   // TK/0
    protected int $ptkpMarried = 58500000;  // K/0
    protected int $ptkpChild = 4500000;     // per anak, max 3

    // Tarif pajak progresif (UU HPP)
    protected array $taxBrackets = [
        60000000 => 0.05,
        250000000 => 0.15,
        500000000 => 0.25,
        5000000000 => 0.30,
        PHP_INT_MAX => 0.35,
    ];

    public function hitung(
        int $basicSalary,
        int $allowance = 0,
        bool $isMarried = false,
        int $children = 0,
        int $bpjsKaryawan = 0
    ): array {
        // Step 1: Hitung bruto bulanan
        $brutoBulanan = $basicSalary + $allowance;

        // Step 2: Biaya jabatan 5% max 500rb
        $biayaJabatan = min($brutoBulanan * 0.05, 500000);

        // Step 3: Hitung neto bulanan
        $netoBulanan = $brutoBulanan - $biayaJabatan - $bpjsKaryawan;

        // Step 4: Hitung neto tahunan
        $netoTahunan = $netoBulanan * 12;

        // Step 5: Tentukan PTKP
        $maxChildren = min($children, 3);
        $ptkp = $this->ptkpSingle;
        if ($isMarried) {
            $ptkp = $this->ptkpMarried + ($maxChildren * $this->ptkpChild);
        }

        // Step 6: PKP (dibulatkan ke ribuan)
        $pkp = max(0, $netoTahunan - $ptkp);
        $pkp = floor($pkp / 1000) * 1000;

        // Step 7: Hitung pajak progresif
        $pajakTahunan = $this->hitungPajakProgresif($pkp);

        // Step 8: Bagi 12 untuk bulanan
        $pajakBulanan = $pajakTahunan / 12;

        return [
            'monthly_gross'     => $brutoBulanan,
            'position_cost'     => $biayaJabatan,
            'monthly_net'       => $netoBulanan,
            'annual_net'        => $netoTahunan,
            'non_taxable_income'            => $ptkp,
            'taxable_income'             => $pkp,
            'annual_pph21'   => $pajakTahunan,
            'monthly_pph21'   => round($pajakBulanan),
        ];
    }

    protected function hitungPajakProgresif(int $pkp): float
    {
        $sisa = $pkp;
        $pajak = 0;
        $batasSebelumnya = 0;

        foreach ($this->taxBrackets as $batas => $tarif) {
            if ($sisa <= 0) {
                break;
            }
            $lapisan = min($sisa, $batas - $batasSebelumnya);
            $pajak += $lapisan * $tarif;
            $sisa -= $lapisan;
            $batasSebelumnya = $batas;
        }

        return $pajak;
    }
}