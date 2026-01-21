<?php

namespace App\Auth\Application\Command\RefreshToken;

use App\Auth\Application\Command\LoginUser\LoginUserResponse;
use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;

final readonly class RefreshTokenHandler
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private UserRepositoryInterface $userRepository,
        private TokenGeneratorInterface $tokenGenerator
    ) {
    }

    public function __invoke(RefreshTokenCommand $command): LoginUserResponse
    {
        // Find and validate refresh token
        $refreshToken = $this->refreshTokenRepository->findByToken($command->refreshToken);

        if (null === $refreshToken) {
            throw new InvalidTokenException('Invalid refresh token');
        }

        if ($refreshToken->isExpired()) {
            // Clean up expired token
            $this->refreshTokenRepository->deleteByToken($command->refreshToken);

            throw new InvalidTokenException('Refresh token has expired');
        }

        // Get user
        $user = $this->userRepository->findById($refreshToken->userId());

        if (null === $user) {
            throw new InvalidTokenException('User not found for refresh token');
        }

        // Generate new access token
        $accessToken = $this->tokenGenerator->generate($user);

        // Rotate refresh token (security best practice)
        // Delete old refresh token
        $this->refreshTokenRepository->deleteByToken($command->refreshToken);

        // Generate new refresh token
        $newRefreshToken = \App\Auth\Domain\Entity\RefreshToken::generate($user->getId());
        $this->refreshTokenRepository->save($newRefreshToken);

        return new LoginUserResponse(
            accessToken: $accessToken->value(),
            refreshToken: $newRefreshToken->token(),
            expiresIn: 900 // 15 minutes for access token
        );
    }
}
