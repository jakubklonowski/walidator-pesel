<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel\Validation;

use App\Pesel\Validation\PeselValidator;
use App\Pesel\Validation\ViolationCode;
use PHPUnit\Framework\TestCase;

final class PeselValidatorTest extends TestCase
{
    /**
     * @dataProvider validPeselProvider
     */
    public function testValidNumbersProduceNoViolations(string $pesel): void
    {
        $result = PeselValidator::default()->validate($pesel);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->violations());
        self::assertNull($result->first());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validPeselProvider(): iterable
    {
        yield 'male, 1944' => ['44051401359'];
        yield 'female, 1990' => ['90011500020'];
        yield 'leading zeros, 2000 leap day' => ['00222900016'];
        yield '22nd century' => ['00410100017'];
    }

    public function testStructuralFailureStopsBeforeTheSemanticRules(): void
    {
        $result = PeselValidator::default()->validate('nonsense');

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->violations());
        self::assertSame(ViolationCode::InvalidLength, $result->violations()[0]->code);
    }

    public function testSemanticRulesAllReportTogether(): void
    {
        $result = PeselValidator::default()->validate('00022900011');

        $codes = array_map(
            static fn ($violation): string => $violation->code->value,
            $result->violations(),
        );

        self::assertSame(
            [ViolationCode::InvalidBirthDate->value, ViolationCode::InvalidChecksum->value],
            $codes,
        );
    }

    public function testChecksumAloneCanFail(): void
    {
        $result = PeselValidator::default()->validate('44051401358');

        self::assertCount(1, $result->violations());
        self::assertSame(ViolationCode::InvalidChecksum, $result->violations()[0]->code);
    }
}
