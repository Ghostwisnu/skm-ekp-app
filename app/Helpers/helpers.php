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
            return '';
        }

        if ($number < 0) {
            return 'Minus ' . terbilang(-$number);
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
            $tens = (int) ($number / 10);
            $ones = $number % 10;
            $result = terbilang($tens) . ' Puluh';
            if ($ones > 0) {
                $result .= ' ' . terbilang($ones);
            }
        } elseif ($number < 200) {
            $result = ' Seratus' . ' ' . terbilang($number - 100);
        } elseif ($number < 1000) {
            $hundreds = (int) ($number / 100);
            $remainder = $number % 100;
            $result = terbilang($hundreds) . ' Ratus';
            if ($remainder > 0) {
                $result .= ' ' . terbilang($remainder);
            }
        } elseif ($number < 2000) {
            $result = ' Seribu' . ' ' . terbilang($number - 1000);
        } elseif ($number < 1000000) {
            $thousands = (int) ($number / 1000);
            $remainder = $number % 1000;
            $result = terbilang($thousands) . ' Ribu';
            if ($remainder > 0) {
                $result .= ' ' . terbilang($remainder);
            }
        } elseif ($number < 1000000000) {
            $millions = (int) ($number / 1000000);
            $remainder = $number % 1000000;
            $result = terbilang($millions) . ' Juta';
            if ($remainder > 0) {
                $result .= ' ' . terbilang($remainder);
            }
        } else {
            $billions = (int) ($number / 1000000000);
            $remainder = $number % 1000000000;
            $result = terbilang($billions) . ' Miliar';
            if ($remainder > 0) {
                $result .= ' ' . terbilang($remainder);
            }
        }

        return trim($result);
    }
}