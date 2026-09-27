<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

final class ChecksumRule implements PeselRule
{
    private const WEIGHTS = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
    private const CHECKSUM_POSITION = 10;

    public function check(string $pesel): ?Violation
    {
        $sum = 0;

        foreach (self::WEIGHTS as $position => $weight) {
            $sum += $weight * (int) $pesel[$position];
        }

        if ((10 - $sum % 10) % 10 === (int) $pesel[self::CHECKSUM_POSITION]) {
            return null;
        }

        return new Violation(
            ViolationCode::InvalidChecksum,
            'The checksum digit does not match the preceding ten digits.',
        );
    }
}
