<?php

namespace App\Auth\Application\Command\LoginUser;

final readonly class LoginUserResponse
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public ?array $user = null
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, user?: array}
     */
    public function toArray(): array
    {
        $response = [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
        ];

        if ($this->user !== null) {
            $response['user'] = $this->user;
        }

        return $response;
    }
}
