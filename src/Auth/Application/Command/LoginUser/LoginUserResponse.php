<?php

namespace App\Auth\Application\Command\LoginUser;

final readonly class LoginUserResponse
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn
    ) {}

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
        ];
    }
}
