<?php

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Service\TokenRevokerInterface;
use App\Auth\Domain\ValueObject\Token;
use App\Auth\Domain\ValueObject\UserId;
use Psr\Log\LoggerInterface;

final class JWTTokenRevoker implements TokenRevokerInterface
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly LoggerInterface $logger
    ) {}

    public function revokeAllTokensForUser(UserId $userId): void
    {
        // no-op
    }

    public function revokeToken(Token $token): void
    {
        // For JWT: We revoke the refresh token
        // Access tokens are stateless and expire automatically
        
        $refreshToken = $this->refreshTokenRepository->findByToken($token->value());
        
        if ($refreshToken === null) {
            $this->logger->warning('Attempted to revoke non-existent refresh token', [
                'token' => substr($token->value(), 0, 8) . '...',
            ]);
            return;
        }

        $this->refreshTokenRepository->deleteByToken($token->value());
        
        $this->logger->info('Refresh token revoked successfully', [
            'user_id' => $refreshToken->userId()->value(),
        ]);
    }

    public function isTokenRevoked(Token $plainToken): bool
    {
        return false;
    }

    public function isTokenValid(Token $plainToken): bool
    {
        return true;
    }
}


