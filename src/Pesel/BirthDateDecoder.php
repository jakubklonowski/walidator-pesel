<?php

declare(strict_types=1);

namespace App\Pesel;

final class BirthDateDecoder
{
    public static function decode(string $pesel): ?\DateTimeImmutable
    {
        if ((11 !== strlen($pesel)) || (1 !== preg_match('/^\d{11}$/', $pesel))) {
            return null;
        }

        $monthField = (int) substr($pesel, 2, 2);

        $century = match (true) {
            $monthField >= 1 && $monthField <= 12 => 1900,
            $monthField >= 21 && $monthField <= 32 => 2000,
            $monthField >= 41 && $monthField <= 52 => 2100,
            $monthField >= 61 && $monthField <= 72 => 2200,
            $monthField >= 81 && $monthField <= 92 => 1800,
            default => null,
        };

        if (null === $century) {
            return null;
        }

        $year = $century + (int) substr($pesel, 0, 2);
        $month = $monthField % 20;
        $day = (int) substr($pesel, 4, 2);

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            sprintf('%04d-%02d-%02d', $year, $month, $day),
            new \DateTimeZone('UTC'),
        );

        return false === $date ? null : $date;
    }
}
