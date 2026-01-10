<?php

namespace App\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\InvalidTokenException;

final readonly class Token
{
    public function __construct(private string $value)
    {
        $this->assertNotEmpty($value);
    }

    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    private function assertNotEmpty(string $token): void
    {
        if (empty($token)) {
            throw InvalidTokenException::empty();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Token $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
