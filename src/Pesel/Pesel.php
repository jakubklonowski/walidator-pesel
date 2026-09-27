<?php

declare(strict_types=1);

namespace App\Pesel;

use App\Pesel\Exception\InvalidPeselException;
use App\Pesel\Validation\PeselValidator;

final readonly class Pesel
{
    private const GENDER_DIGIT_POSITION = 9;

    // constructor is private, use Pesel::fromString instead
    private function __construct(
        private string $value,
        private \DateTimeImmutable $birthDate,
        private Gender $gender,
    ) {
    }

    public static function fromString(string $value): self
    {
        $violation = PeselValidator::default()->validate($value)->first();

        if (null !== $violation) {
            throw InvalidPeselException::because($violation, $value);
        }

        $birthDate = BirthDateDecoder::decode($value);

        if (null === $birthDate) {
            throw new \LogicException('A validated PESEL must encode a decodable date of birth.');
        }

        return new self($value, $birthDate, Gender::fromSerialDigit((int) $value[self::GENDER_DIGIT_POSITION]));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function birthDate(): \DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function gender(): Gender
    {
        return $this->gender;
    }

    public function masked(): string
    {
        return PeselMask::mask($this->value);
    }
}
