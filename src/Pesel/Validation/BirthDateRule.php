<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

use App\Pesel\BirthDateDecoder;

final class BirthDateRule implements PeselRule
{
    public function check(string $pesel): ?Violation
    {
        if (null !== BirthDateDecoder::decode($pesel)) {
            return null;
        }

        return new Violation(
            ViolationCode::InvalidBirthDate,
            'The first six digits do not encode an existing date of birth.',
        );
    }
}
