<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel\Validation;

use App\Pesel\BirthDateDecoder;
use App\Pesel\Validation\BirthDateRule;
use App\Pesel\Validation\ViolationCode;
use PHPUnit\Framework\TestCase;

final class BirthDateRuleTest extends TestCase
{
    /**
     * @dataProvider centuryProvider
     */
    public function testMonthFieldSelectsTheCentury(string $pesel, string $expectedDate): void
    {
        self::assertNull((new BirthDateRule())->check($pesel));

        $decoded = BirthDateDecoder::decode($pesel);

        self::assertNotNull($decoded);
        self::assertSame($expectedDate, $decoded->format('Y-m-d'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function centuryProvider(): iterable
    {
        yield '1800s: month + 80' => ['00810100000', '1800-01-01'];
        yield '1900s: month as is' => ['44051401359', '1944-05-14'];
        yield '2000s: month + 20, 2000 is a leap year' => ['00222900016', '2000-02-29'];
        yield '2100s: month + 40' => ['00410100017', '2100-01-01'];
        yield '2200s: month + 60' => ['00610100000', '2200-01-01'];
    }

    /**
     * @dataProvider impossibleDateProvider
     */
    public function testImpossibleDateIsReported(string $pesel): void
    {
        $violation = (new BirthDateRule())->check($pesel);

        self::assertNotNull($violation);
        self::assertSame(ViolationCode::InvalidBirthDate, $violation->code);
        self::assertNull(BirthDateDecoder::decode($pesel));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function impossibleDateProvider(): iterable
    {
        yield '29 February 1900, not a leap year' => ['00022900010'];
        yield '31 April' => ['00043100000'];
        yield 'month 00' => ['00003100000'];
        yield 'day 00' => ['00010000000'];
        yield 'month 13, inside the century gap' => ['00131000000'];
        yield 'month 20, inside the century gap' => ['00201000000'];
        yield 'month 93, above every century' => ['00930100000'];
        yield 'all zeros' => ['00000000000'];
    }
}
