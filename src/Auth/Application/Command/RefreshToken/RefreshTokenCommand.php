<?php

namespace App\Auth\Application\Command\RefreshToken;

final readonly class RefreshTokenCommand
{
    public function __construct(
        public string $refreshToken
    ) {
    }
}
