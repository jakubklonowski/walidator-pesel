<?php

declare(strict_types=1);

namespace App\Pesel;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';

    public static function fromSerialDigit(int $digit): self
    {
        return 0 === $digit % 2 ? self::Female : self::Male;
    }
}
