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
        // Wrap Domain User in SecurityUser adapter
        $securityUser = new SecurityUser($user);

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

            if (!isset($payload['sub'])) {
                return null;
            }

            return UserId::fromString($payload['sub']);
        } catch (\Exception $e) {
            return null;
        }
    }
}
