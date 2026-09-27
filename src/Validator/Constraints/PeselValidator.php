<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Pesel\PeselMask;
use App\Pesel\Validation\PeselValidator as DomainValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class PeselValidator extends ConstraintValidator
{
    public function __construct(private readonly DomainValidator $validator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Pesel) {
            throw new UnexpectedTypeException($constraint, Pesel::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $violation = $this->validator->validate($value)->first();

        if (null === $violation) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue(PeselMask::mask($value)))
            ->setCode($violation->code->value)
            ->addViolation();
    }
}
