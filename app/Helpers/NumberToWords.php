<?php

namespace App\Helpers;

class NumberToWords
{
    private static $units = [
        '',
        'uno', 'dos', 'tres', 'cuatro', 'cinco',
        'seis', 'siete', 'ocho', 'nueve', 'diez',
        'once', 'doce', 'trece', 'catorce', 'quince',
        'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte'
    ];

    private static $tens = [
        '', '',
        'veinti', 'treinta', 'cuarenta', 'cincuenta',
        'sesenta', 'setenta', 'ochenta', 'noventa'
    ];

    private static $hundreds = [
        '', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos',
        'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'
    ];

    private static $thousands = [
        '', 'mil', 'millón', 'millones', 'mil millones'
    ];

    public static function convert(float $number): string
    {
        $whole = (int) $number;
        $cents = (int) round(($number - $whole) * 100);

        $result = self::convertInteger($whole);

        if ($cents > 0) {
            $result .= ' con ' . self::convertInteger($cents) . ' centavos';
        }

        return $result;
    }

    private static function convertInteger(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }

        if ($number < 0) {
            return 'menos ' . self::convertInteger(-$number);
        }

        if ($number <= 20) {
            return self::$units[$number];
        }

        if ($number < 30) {
            $unit = $number - 20;
            return 'veinti' . self::$units[$unit];
        }

        if ($number < 100) {
            $ten = (int) ($number / 10);
            $unit = $number % 10;
            return self::$tens[$ten] . ($unit > 0 ? ' y ' . self::$units[$unit] : '');
        }

        if ($number === 100) {
            return 'cien';
        }

        if ($number < 1000) {
            $hundred = (int) ($number / 100);
            $rest = $number % 100;
            return self::$hundreds[$hundred] . ($rest > 0 ? ' ' . self::convertInteger($rest) : '');
        }

        if ($number < 2000) {
            $rest = $number % 1000;
            return 'mil' . ($rest > 0 ? ' ' . self::convertInteger($rest) : '');
        }

        if ($number < 1000000) {
            $thousands = (int) ($number / 1000);
            $rest = $number % 1000;
            return self::convertInteger($thousands) . ' mil' . ($rest > 0 ? ' ' . self::convertInteger($rest) : '');
        }

        if ($number === 1000000) {
            return 'un millón';
        }

        if ($number < 2000000) {
            $rest = $number % 1000000;
            return 'un millón' . ($rest > 0 ? ' ' . self::convertInteger($rest) : '');
        }

        $millions = (int) ($number / 1000000);
        $rest = $number % 1000000;
        return self::convertInteger($millions) . ' millones' . ($rest > 0 ? ' ' . self::convertInteger($rest) : '');
    }
}
