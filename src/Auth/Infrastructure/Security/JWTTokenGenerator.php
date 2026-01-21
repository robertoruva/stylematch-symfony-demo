<?php

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Token;
use App\Auth\Domain\ValueObject\UserId;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class JWTTokenGenerator implements TokenGeneratorInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function generate(User $user): Token
    {
        // Create SecurityUser adapter with individual parameters from Domain User
        $securityUser = new SecurityUser(
            id: $user->getId()->value(),
            email: $user->getEmail()->value(),
            password: $user->getPassword()->value(),
            name: $user->getName()
        );

        // Generate JWT token using Lexik
        $token = $this->jwtManager->createFromPayload(
            $securityUser,
            $securityUser->getJWTPayload()
        );

        return Token::fromString($token);
    }

    public function verify(Token $token): ?UserId
    {
        try {
            $payload = $this->jwtManager->parse($token->value());

            // Try user_id first (our custom claim), then sub (Lexik default)
            $userId = $payload['user_id'] ?? $payload['sub'] ?? null;

            if (!$userId) {
                return null;
            }

            return UserId::fromString($userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
