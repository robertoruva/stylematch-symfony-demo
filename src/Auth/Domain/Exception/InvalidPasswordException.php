<?php

namespace App\Auth\Domain\Exception;

final class InvalidPasswordException extends \DomainException
{
    public static function tooShort(): self
    {
        return new self('Password must be at least 8 characters.');
    }

    public static function invalidHash(): self
    {
        return new self('Invalid password hash format.');
    }
}
