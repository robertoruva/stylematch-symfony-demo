<?php

namespace App\Auth\Application\Command\LoginUser;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Domain\Exception\UserNotFoundException;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\PasswordComparerInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;

final class LoginUserHandler
{
    public const TIME_TOKEN_DEFAULT = 604800;  // 7 days
    public const TIME_TOKEN_REFRESH = 900; // 15 minutes

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordComparerInterface $passwordComparer,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository
    ) {
    }

    public function __invoke(LoginUserCommand $command): LoginUserResponse
    {
        $email = new Email($command->email);
        $user = $this->userRepository->findByEmail($email);

        if (null === $user) {
            throw new UserNotFoundException($email);
        }

        // Verify password
        $isValid = $this->passwordComparer->verify($command->password, $user->getPassword());
        
        if (!$isValid) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $accessToken = $this->tokenGenerator->generate($user);

        $refreshToken = RefreshToken::generate(
            userId: $user->getId(),
            ttlSeconds: self::TIME_TOKEN_DEFAULT
        );

        $this->refreshTokenRepository->save($refreshToken);

        return new LoginUserResponse(
            accessToken: $accessToken->value(),
            refreshToken: $refreshToken->token(),
            expiresIn: self::TIME_TOKEN_REFRESH
        );
    }
}
