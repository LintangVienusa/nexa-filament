<?php

namespace App\Services;

class FixedAllowanceService
{
    
    public function hitung(
        int $basicSalary,
        bool $marital_status = false,
        int $number_of_children = 0,
        int $positionalAllowance = 0,
        int $housingAllowance = 0,
    ): array {
        // Maksimal 3 anak sesuai aturan umum
        $number_of_children = max(0, min($number_of_children, 3));

        $marriageAllowance = $marital_status ? (0.10 * $basicSalary) : 0;
        $childAllowance  = $number_of_children * (0.05 * $basicSalary);
        
         $positionalAllowance = 0;
         $housingAllowance = 0;

        $FixedAllowance = $housingAllowance + $positionalAllowance + $marriageAllowance + $childAllowance;

        return [
            'positional_allowance'   => $positionalAllowance,
            'housing_allowance' => $housingAllowance,
            'marriage_allowance'     => $marriageAllowance,
            'child_allowance'      => $childAllowance,
            'Fixed_allowance'     => $FixedAllowance,
        ];
    }
}