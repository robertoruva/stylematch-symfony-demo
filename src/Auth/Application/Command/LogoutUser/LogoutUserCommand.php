<?php

namespace App\Auth\Application\Command\LogoutUser;

final class LogoutUserCommand
{
    public function __construct(
        public readonly string $token
    ) {
    }
}
