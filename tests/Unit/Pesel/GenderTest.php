<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel;

use App\Pesel\Gender;
use PHPUnit\Framework\TestCase;

final class GenderTest extends TestCase
{
    /**
     * @dataProvider serialDigitProvider
     */
    public function testSerialDigitParityDecidesGender(int $digit, Gender $expected): void
    {
        self::assertSame($expected, Gender::fromSerialDigit($digit));
    }

    /**
     * @return iterable<string, array{int, Gender}>
     */
    public static function serialDigitProvider(): iterable
    {
        foreach ([0, 2, 4, 6, 8] as $digit) {
            yield sprintf('%d is female', $digit) => [$digit, Gender::Female];
        }

        foreach ([1, 3, 5, 7, 9] as $digit) {
            yield sprintf('%d is male', $digit) => [$digit, Gender::Male];
        }
    }
}
