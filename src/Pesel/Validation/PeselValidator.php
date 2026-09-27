<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

final class PeselValidator
{
    /**
     * @param iterable<PeselRule> $structuralRules
     * @param iterable<PeselRule> $semanticRules
     */
    public function __construct(
        private readonly iterable $structuralRules,
        private readonly iterable $semanticRules,
    ) {
    }

    public static function default(): self
    {
        return new self(
            [new LengthAndDigitsRule()],
            [new BirthDateRule(), new ChecksumRule()],
        );
    }

    public function validate(string $pesel): ValidationResult
    {
        $structural = $this->apply($this->structuralRules, $pesel);

        if ([] !== $structural) {
            return new ValidationResult(...$structural);
        }

        return new ValidationResult(...$this->apply($this->semanticRules, $pesel));
    }

    /**
     * @param iterable<PeselRule> $rules
     *
     * @return list<Violation>
     */
    private function apply(iterable $rules, string $pesel): array
    {
        $violations = [];

        foreach ($rules as $rule) {
            $violation = $rule->check($pesel);

            if (null !== $violation) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }
}
