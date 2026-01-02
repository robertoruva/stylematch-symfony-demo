<?php

namespace App\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\InvalidTokenException;

final class TokenString
{
    private readonly string $value;

    public function __construct(string $token)
    {
        $token = trim($token);
        $this->assertValid($token);

        $this->value = $token;
    }

    private function assertValid(string $token): void
    {
        if ('' === $token) {
            throw InvalidTokenException::empty();
        }

        // Validación opcional de formato Sanctum:
        // {id}|{plainTextToken}
        if (!preg_match('/^\d+\|.+$/', $token)) {
            throw InvalidTokenException::invalidFormat($token);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getTokenId(): int
    {
        [$id] = explode('|', $this->value, 2);

        return (int) $id;
    }

    public function getPlainText(): string
    {
        [,$plainText] = explode('|', $this->value, 2);

        return $plainText;
    }

    public function equals(TokenString $other): bool
    {
        return $this->value === $other->value;
    }
}
