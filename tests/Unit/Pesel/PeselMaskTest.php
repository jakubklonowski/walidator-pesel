<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel;

use App\Pesel\PeselMask;
use PHPUnit\Framework\TestCase;

final class PeselMaskTest extends TestCase
{
    /**
     * @dataProvider maskProvider
     */
    public function testMasking(string $input, string $expected): void
    {
        self::assertSame($expected, PeselMask::mask($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function maskProvider(): iterable
    {
        yield 'full length keeps two digits on each side' => ['44051401359', '44*******59'];
        yield 'five characters' => ['12345', '12*45'];
        yield 'four characters are fully hidden' => ['1234', '****'];
        yield 'empty input stays empty' => ['', ''];
    }
}
