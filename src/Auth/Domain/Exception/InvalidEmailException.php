<?php

namespace App\Auth\Domain\Exception;

final class InvalidEmailException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("Invalid email provided: {$email}");
    }
}
