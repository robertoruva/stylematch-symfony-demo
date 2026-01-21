<?php

namespace App\Auth\Application\Command\LoginUser;

final readonly class LoginUserResponse
{
    /**
     * @param array{id: string, name: string, email: string}|null $user
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public ?array $user = null
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, user?: array{id: string, name: string, email: string}}
     */
    public function toArray(): array
    {
        $response = [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
        ];

        if (null !== $this->user) {
            $response['user'] = $this->user;
        }

        return $response;
    }
}
