<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel;

use App\Pesel\Exception\InvalidPeselException;
use App\Pesel\Gender;
use App\Pesel\Pesel;
use App\Pesel\Validation\ViolationCode;
use PHPUnit\Framework\TestCase;

final class PeselTest extends TestCase
{
    /**
     * @dataProvider decodedPeselProvider
     */
    public function testValidNumberDecodesIntoBirthDateAndGender(string $pesel, string $birthDate, Gender $gender): void
    {
        $value = Pesel::fromString($pesel);

        self::assertSame($pesel, $value->value());
        self::assertSame($birthDate, $value->birthDate()->format('Y-m-d'));
        self::assertSame($gender, $value->gender());
    }

    /**
     * @return iterable<string, array{string, string, Gender}>
     */
    public static function decodedPeselProvider(): iterable
    {
        yield 'odd serial digit is male' => ['44051401359', '1944-05-14', Gender::Male];
        yield 'even serial digit is female' => ['90011500020', '1990-01-15', Gender::Female];
        yield 'leading zeros survive' => ['00222900016', '2000-02-29', Gender::Male];
    }

    public function testBirthDateIsAlwaysUtcMidnight(): void
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('Pacific/Auckland');

        try {
            $birthDate = Pesel::fromString('44051401359')->birthDate();
        } finally {
            date_default_timezone_set($defaultTimezone);
        }

        self::assertSame('UTC', $birthDate->getTimezone()->getName());
        self::assertSame('00:00:00', $birthDate->format('H:i:s'));
    }

    public function testInvalidNumberThrowsWithTheFirstViolationCode(): void
    {
        $this->expectException(InvalidPeselException::class);

        try {
            Pesel::fromString('00022900011');
        } catch (InvalidPeselException $exception) {
            self::assertSame(ViolationCode::InvalidBirthDate, $exception->violationCode);

            throw $exception;
        }
    }

    public function testExceptionMessageNeverLeaksTheFullNumber(): void
    {
        try {
            Pesel::fromString('00022900010');
            self::fail('Expected an InvalidPeselException.');
        } catch (InvalidPeselException $exception) {
            self::assertStringNotContainsString('00022900010', $exception->getMessage());
            self::assertStringContainsString('00*******10', $exception->getMessage());
        }
    }

    public function testMaskedHidesTheMiddleDigits(): void
    {
        self::assertSame('44*******59', Pesel::fromString('44051401359')->masked());
    }
}
