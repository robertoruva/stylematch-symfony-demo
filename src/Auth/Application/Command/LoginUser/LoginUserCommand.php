<?php

namespace App\Auth\Application\Command\LoginUser;

class LoginUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
