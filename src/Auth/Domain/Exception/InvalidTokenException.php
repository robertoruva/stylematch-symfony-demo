<?php

namespace App\Auth\Domain\Exception;

final class InvalidTokenException extends \DomainException
{
    public static function empty(): self
    {
        return new self('Token cannot be empty');
    }

    public static function invalidFormat(string $token): self
    {
        $preview = substr($token, 0, 10);

        return new self("Invalid bearer token format: {$preview}...");
    }
}
