<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel\Validation;

use App\Pesel\Validation\ChecksumRule;
use App\Pesel\Validation\ViolationCode;
use PHPUnit\Framework\TestCase;

final class ChecksumRuleTest extends TestCase
{
    /**
     * @dataProvider correctChecksumProvider
     */
    public function testCorrectChecksumPasses(string $pesel): void
    {
        self::assertNull((new ChecksumRule())->check($pesel));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function correctChecksumProvider(): iterable
    {
        yield 'checksum 9' => ['44051401359'];
        yield 'checksum 0' => ['90011500020'];
        yield 'all zeros' => ['00000000000'];
    }

    /**
     * @dataProvider wrongChecksumProvider
     */
    public function testWrongChecksumIsReported(string $pesel): void
    {
        $violation = (new ChecksumRule())->check($pesel);

        self::assertNotNull($violation);
        self::assertSame(ViolationCode::InvalidChecksum, $violation->code);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function wrongChecksumProvider(): iterable
    {
        yield 'last digit off by one' => ['44051401358'];
        yield 'two digits transposed' => ['44051401395'];
    }

    public function testEverySingleDigitChangeIsDetected(): void
    {
        $rule = new ChecksumRule();
        $valid = '44051401359';

        for ($position = 0; $position < 11; ++$position) {
            for ($digit = 0; $digit <= 9; ++$digit) {
                $mutated = $valid;
                $mutated[$position] = (string) $digit;

                if ($mutated === $valid) {
                    continue;
                }

                self::assertNotNull(
                    $rule->check($mutated),
                    sprintf('Changing position %d to %d went undetected.', $position, $digit),
                );
            }
        }
    }
}
