<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\IValueBinder;

/**
 * FormulaValueBinder
 *
 * Class ini digunakan untuk membaca **nilai hasil formula** dari Excel
 * bukan string rumusnya. Cocok digunakan dengan Maatwebsite Excel.
 */
class FormulaValueBinderService extends DefaultValueBinder implements IValueBinder
{
    public function bindValue(Cell $cell, $value)
    {
        // Jika sel berisi formula, ambil nilai hasilnya
        if ($cell->getValue() !== null && substr($cell->getValue(), 0, 1) === '=') {
            $calculatedValue = $cell->getCalculatedValue();
            $cell->setValue($calculatedValue);
            return true;
        }

        // Jika bukan formula, gunakan binder default
        return parent::bindValue($cell, $value);
    }
}
