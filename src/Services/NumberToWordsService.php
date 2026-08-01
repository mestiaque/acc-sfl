<?php

namespace ME\AccSfl\Services;

class NumberToWordsService
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public function taka(float $amount): string
    {
        $taka = (int) floor($amount);
        $paisa = (int) round(($amount - $taka) * 100);

        $words = trim($this->convertUsingIndianSystem($taka)).' Taka';

        if ($paisa > 0) {
            $words .= ' and '.trim($this->convertUsingIndianSystem($paisa)).' Paisa';
        }

        return $words.' Only';
    }

    private function convertUsingIndianSystem(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $rest = $number % 100;

        $parts = [];

        if ($crore) {
            $parts[] = $this->convertUnderHundred($crore).' Crore';
        }
        if ($lakh) {
            $parts[] = $this->convertUnderHundred($lakh).' Lakh';
        }
        if ($thousand) {
            $parts[] = $this->convertUnderHundred($thousand).' Thousand';
        }
        if ($hundred) {
            $parts[] = self::ONES[$hundred].' Hundred';
        }
        if ($rest) {
            $parts[] = $this->convertUnderHundred($rest);
        }

        return implode(' ', $parts);
    }

    private function convertUnderHundred(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        $tens = intdiv($number, 10);
        $ones = $number % 10;

        return trim(self::TENS[$tens].' '.self::ONES[$ones]);
    }
}
