<?php

declare(strict_types=1);

namespace App\Tests\Unit\Pesel\Validation;

use App\Pesel\Validation\LengthAndDigitsRule;
use App\Pesel\Validation\ViolationCode;
use PHPUnit\Framework\TestCase;

final class LengthAndDigitsRuleTest extends TestCase
{
    public function testElevenDigitsPass(): void
    {
        self::assertNull((new LengthAndDigitsRule())->check('44051401359'));
    }

    /**
     * @dataProvider wrongLengthProvider
     */
    public function testWrongLengthIsReported(string $pesel): void
    {
        $violation = (new LengthAndDigitsRule())->check($pesel);

        self::assertNotNull($violation);
        self::assertSame(ViolationCode::InvalidLength, $violation->code);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function wrongLengthProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'ten digits' => ['4405140135'];
        yield 'twelve digits' => ['440514013590'];
    }

    /**
     * @dataProvider nonDigitProvider
     */
    public function testNonDigitCharactersAreReported(string $pesel): void
    {
        $violation = (new LengthAndDigitsRule())->check($pesel);

        self::assertNotNull($violation);
        self::assertSame(ViolationCode::NotDigits, $violation->code);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonDigitProvider(): iterable
    {
        yield 'trailing letter' => ['4405140135X'];
        yield 'embedded space' => ['4405 401359'];
        yield 'trailing newline' => ["4405140135\n"];
        yield 'signed number' => ['+4405140135'];
    }
}
