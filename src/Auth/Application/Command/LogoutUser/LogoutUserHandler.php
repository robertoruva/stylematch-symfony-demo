<?php

namespace App\Auth\Application\Command\LogoutUser;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Service\TokenRevokerInterface;
use App\Auth\Domain\ValueObject\Token;
use Psr\Log\LoggerInterface;

final class LogoutUserHandler
{
    public function __construct(
        private readonly TokenRevokerInterface $tokenRevoker,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(LogoutUserCommand $command): void
    {
        $this->logger->info('LOGOUT: Starting logout', ['token' => substr($command->token, 0, 16) . '...']);

        try {
            $token = new Token($command->token);
            $this->logger->info('LOGOUT: TokenString created');

            $this->tokenRevoker->revokeToken($token);
            $this->logger->info('LOGOUT: Token revoked successfully');
            
        } catch (InvalidTokenException $e) {
            $this->logger->warning('Logout attempted with invalid token format', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
