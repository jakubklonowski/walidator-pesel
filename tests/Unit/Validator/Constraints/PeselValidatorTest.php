<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator\Constraints;

use App\Pesel\Validation\PeselValidator as DomainValidator;
use App\Pesel\Validation\ViolationCode;
use App\Validator\Constraints\Pesel;
use App\Validator\Constraints\PeselValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class PeselValidatorTest extends ConstraintValidatorTestCase
{
    public function testValidPeselRaisesNoViolation(): void
    {
        $this->validator->validate('44051401359', new Pesel());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider emptyValueProvider
     */
    public function testEmptyValuesAreLeftToOtherConstraints(?string $value): void
    {
        $this->validator->validate($value, new Pesel());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function emptyValueProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    public function testInvalidPeselRaisesMaskedViolationCarryingTheCode(): void
    {
        $constraint = new Pesel();

        $this->validator->validate('00022900010', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"00*******10"')
            ->setCode(ViolationCode::InvalidBirthDate->value)
            ->assertRaised();
    }

    public function testSeveralFailingRulesRaiseOnlyTheFirstViolation(): void
    {
        $constraint = new Pesel();

        $this->validator->validate('00022900011', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"00*******11"')
            ->setCode(ViolationCode::InvalidBirthDate->value)
            ->assertRaised();
    }

    public function testWrongConstraintTypeIsRejected(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('44051401359', new NotBlank());
    }

    public function testNonStringValueIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(44051401359, new Pesel());
    }

    protected function createValidator(): PeselValidator
    {
        return new PeselValidator(DomainValidator::default());
    }
}
