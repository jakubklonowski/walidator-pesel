<?php

declare(strict_types=1);

namespace App\Pesel\Exception;

use App\Pesel\PeselMask;
use App\Pesel\Validation\Violation;
use App\Pesel\Validation\ViolationCode;

final class InvalidPeselException extends \InvalidArgumentException
{
    private function __construct(
        public readonly ViolationCode $violationCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function because(Violation $violation, string $value): self
    {
        return new self(
            $violation->code,
            sprintf('Invalid PESEL "%s": %s', PeselMask::mask($value), $violation->message),
        );
    }
}
