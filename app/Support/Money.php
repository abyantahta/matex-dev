<?php

namespace App\Support;

class Money
{
    /**
     * Harga bilangan bulat tampil tanpa .00; desimal tetap dipertahankan.
     */
    public static function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;

        if (! is_finite($number)) {
            return '';
        }

        if (abs($number - round($number)) < 0.0000001) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }
}
