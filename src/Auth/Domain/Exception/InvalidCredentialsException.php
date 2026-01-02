<?php

namespace App\Auth\Domain\Exception;

final class InvalidCredentialsException extends \DomainException
{
    public static function create(): self
    {
        return new self('Invalid email or password.');
    }
}
