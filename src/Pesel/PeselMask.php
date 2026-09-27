<?php

declare(strict_types=1);

namespace App\Pesel;

final class PeselMask
{
    private const VISIBLE_PREFIX = 2;
    private const VISIBLE_SUFFIX = 2;
    private const MASK_CHARACTER = '*';

    public static function mask(string $value): string
    {
        $length = strlen($value);

        if ($length <= self::VISIBLE_PREFIX + self::VISIBLE_SUFFIX) {
            return str_repeat(self::MASK_CHARACTER, $length);
        }

        return substr($value, 0, self::VISIBLE_PREFIX)
            .str_repeat(self::MASK_CHARACTER, $length - self::VISIBLE_PREFIX - self::VISIBLE_SUFFIX)
            .substr($value, -self::VISIBLE_SUFFIX);
    }
}
