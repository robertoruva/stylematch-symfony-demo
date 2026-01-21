<?php

namespace App\Auth\Application\Command\LogoutUser;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Token;
use Psr\Log\LoggerInterface;

final class LogoutUserHandler
{
    public function __construct(
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(LogoutUserCommand $command): void
    {
        $this->logger->info('LOGOUT: Starting logout', ['token' => substr($command->token, 0, 16).'...']);

        try {
            $token = new Token($command->token);
            $this->logger->info('LOGOUT: Token created');

            // Extract userId from JWT
            $userId = $this->tokenGenerator->verify($token);

            if (null === $userId) {
                $this->logger->warning('Invalid or expired JWT token during logout - logout successful anyway');
                return;
            }

            $this->logger->info('LOGOUT: UserId extracted from JWT', ['userId' => $userId->value()]);

            // Delete all refresh tokens for this user from Redis
            $this->refreshTokenRepository->deleteAllByUserId($userId);

            $this->logger->info('LOGOUT: All refresh tokens deleted for user', ['userId' => $userId->value()]);
        } catch (InvalidTokenException $e) {
            $this->logger->warning('Logout attempted with invalid token format', [
                'error' => $e->getMessage(),
            ]);
            // Don't throw exception - logout is idempotent
            return;
        }
    }
}
