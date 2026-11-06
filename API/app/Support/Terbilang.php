<?php

namespace App\Support;

class Terbilang
{
    public static function rupiah(int $amount): string
    {
        $spell = trim(self::spell(abs($amount)));

        return ucfirst($spell === '' ? 'nol' : $spell).' rupiah';
    }

    private static function spell(int $amount): string
    {
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($amount < 12) {
            return $words[$amount];
        }

        if ($amount < 20) {
            return self::spell($amount - 10).' belas';
        }

        if ($amount < 100) {
            return trim(self::spell(intdiv($amount, 10)).' puluh '.self::spell($amount % 10));
        }

        if ($amount < 200) {
            return trim('seratus '.self::spell($amount - 100));
        }

        if ($amount < 1000) {
            return trim(self::spell(intdiv($amount, 100)).' ratus '.self::spell($amount % 100));
        }

        if ($amount < 2000) {
            return trim('seribu '.self::spell($amount - 1000));
        }

        if ($amount < 1000000) {
            return trim(self::spell(intdiv($amount, 1000)).' ribu '.self::spell($amount % 1000));
        }

        if ($amount < 1000000000) {
            return trim(self::spell(intdiv($amount, 1000000)).' juta '.self::spell($amount % 1000000));
        }

        return trim(self::spell(intdiv($amount, 1000000000)).' miliar '.self::spell($amount % 1000000000));
    }
}
