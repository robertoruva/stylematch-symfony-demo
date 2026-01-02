<?php

namespace App\Auth\Application\Command\RegisterUser;

class RegisterUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }
}
