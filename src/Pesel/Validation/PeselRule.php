<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

interface PeselRule
{
    public function check(string $pesel): ?Violation;
}
