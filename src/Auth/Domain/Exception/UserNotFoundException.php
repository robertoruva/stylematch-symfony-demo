<?php

namespace App\Auth\Domain\Exception;

final class UserNotFoundException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("User with email {$email} not found.");
    }
}
