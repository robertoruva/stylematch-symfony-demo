<?php

namespace App\Auth\Application\Command\LogoutUser;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Service\TokenRevokerInterface;
use App\Auth\Domain\ValueObject\TokenString;
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
        try {
            $token = new TokenString($command->token);
            $this->tokenRevoker->revokeToken($token);
        } catch (InvalidTokenException $e) {
            $this->logger->warning('Logout attempted with invalid token format', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
