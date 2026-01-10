<?php

namespace App\Auth\Application\Command\LoginUser;

final readonly class LoginUserResponse
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}
     */
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
