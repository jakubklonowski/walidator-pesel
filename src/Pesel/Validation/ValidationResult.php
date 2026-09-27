<?php

declare(strict_types=1);

namespace App\Pesel\Validation;

final readonly class ValidationResult
{
    /** @var list<Violation> */
    private array $violations;

    public function __construct(Violation ...$violations)
    {
        $this->violations = array_values($violations);
    }

    public function isValid(): bool
    {
        return [] === $this->violations;
    }

    /** @return list<Violation> */
    public function violations(): array
    {
        return $this->violations;
    }

    public function first(): ?Violation
    {
        return $this->violations[0] ?? null;
    }
}
