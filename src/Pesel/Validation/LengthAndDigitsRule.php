<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

final class LengthAndDigitsRule implements PeselRule
{
    public const LENGTH = 11;

    public function check(string $pesel): ?Violation
    {
        $length = strlen($pesel);

        if (self::LENGTH !== $length) {
            return new Violation(
                ViolationCode::InvalidLength,
                sprintf('PESEL must be exactly %d characters long, %d given.', self::LENGTH, $length),
            );
        }

        if (1 !== preg_match('/\A\d+\z/', $pesel)) {
            return new Violation(
                ViolationCode::NotDigits,
                'PESEL must consist of digits only.',
            );
        }

        return null;
    }
}
