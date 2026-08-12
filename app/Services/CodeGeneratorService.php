<?php

namespace App\Services;

class CodeGeneratorService
{
    public function generate(
        string $modelClass,
        string $column = 'department_code',
        string $prefix = 'DPT',
        int $digit = 3
    ): string {

        $lastCode = $modelClass::where($column, 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByRaw("CAST(SUBSTRING($column, " . (strlen($prefix) + 1) . ", $digit) AS UNSIGNED) DESC")
            ->first();

        if (!$lastCode) {
            $number = 1;
            $letter = 'A';
        } else {
            $lastValue = $lastCode->$column;

            $number = intval(substr($lastValue, strlen($prefix), $digit));
            $letter = substr($lastValue, -1);

            if ($number < pow(10, $digit) - 1) {
                $number++;
            } else {
                $number = 1;
                $letter++;
            }
        }

        return $prefix . str_pad($number, $digit, '0', STR_PAD_LEFT) . $letter;
    }
}