<?php

if (! function_exists('terbilang')) {
    /**
     * Konversi angka ke terbilang Indonesia.
     *
     * @param  int|float  $number
     * @return string
     */
    function terbilang($number): string
    {
        $number = (int) $number;

        if ($number === 0) {
            return 'Nol';
        }

        $words = [
            '',
            'Satu',
            'Dua',
            'Tiga',
            'Empat',
            'Lima',
            'Enam',
            'Tujuh',
            'Delapan',
            'Sembilan',
            'Sepuluh',
            'Sebelas',
        ];

        $result = '';

        if ($number < 12) {
            $result = ' ' . $words[$number];
        } elseif ($number < 20) {
            $result = terbilang($number - 10) . ' Belas';
        } elseif ($number < 100) {
            $result = terbilang((int) ($number / 10)) . ' Puluh' . terbilang($number % 10);
        } elseif ($number < 200) {
            $result = ' Seratus' . terbilang($number - 100);
        } elseif ($number < 1000) {
            $result = terbilang((int) ($number / 100)) . ' Ratus' . terbilang($number % 100);
        } elseif ($number < 2000) {
            $result = ' Seribu' . terbilang($number - 1000);
        } elseif ($number < 1000000) {
            $result = terbilang((int) ($number / 1000)) . ' Ribu' . terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            $result = terbilang((int) ($number / 1000000)) . ' Juta' . terbilang($number % 1000000);
        } else {
            $result = terbilang((int) ($number / 1000000000)) . ' Miliar' . terbilang($number % 1000000000);
        }

        return trim($result);
    }
}