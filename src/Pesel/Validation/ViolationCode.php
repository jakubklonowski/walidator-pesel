<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

enum ViolationCode: string
{
    case InvalidLength = 'invalid_length';
    case NotDigits = 'not_digits';
    case InvalidBirthDate = 'invalid_birth_date';
    case InvalidChecksum = 'invalid_checksum';
}
