<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

final readonly class Violation
{
    public function __construct(
        public ViolationCode $code,
        public string $message,
    ) {
    }
}
