<?php

namespace App\Tests\Unit\Auth\Application\Command;

use App\Auth\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Auth\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class RefreshTokenHandlerTest extends TestCase
{
    private RefreshTokenRepositoryInterface $refreshTokenRepository;
    private UserRepositoryInterface $userRepository;
    private TokenGeneratorInterface $tokenGenerator;
    private RefreshTokenHandler $handler;

    protected function setUp(): void
    {
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenGenerator = $this->createMock(TokenGeneratorInterface::class);

        $this->handler = new RefreshTokenHandler(
            $this->refreshTokenRepository,
            $this->userRepository,
            $this->tokenGenerator
        );
    }

    public function testItGeneratesNewAccessTokenWithValidRefreshToken(): void
    {
        // Arrange
        $userId = UserId::random();
        $refreshTokenString = 'valid_refresh_token';
        $newAccessToken = 'new_access_token';

        $refreshToken = RefreshToken::generate($userId);
        $user = User::register(
            id: $userId,
            name: 'Test User',
            email: new Email('test@example.com'),
            password: PasswordHash::fromPlainText('password123')
        );

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with($refreshTokenString)
            ->willReturn($refreshToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(function (UserId $id) use ($userId) {
                return $id->value() === $userId->value();
            }))
            ->willReturn($user);

        $tokenObject = new \App\Auth\Domain\ValueObject\Token($newAccessToken);
        $this->tokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($user)
            ->willReturn($tokenObject);

        // Expect old refresh token to be deleted (token rotation)
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('deleteByToken')
            ->with($refreshTokenString);

        // Expect new refresh token to be saved (token rotation)
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RefreshToken $token) use ($userId, $refreshTokenString) {
                // Verify new token is for the same user but is different
                return $token->userId()->value() === $userId->value()
                    && $token->token() !== $refreshTokenString;
            }));

        // Act
        $command = new RefreshTokenCommand($refreshTokenString);
        $result = ($this->handler)($command);

        // Assert
        $this->assertSame($newAccessToken, $result->accessToken);
        $this->assertNotSame($refreshTokenString, $result->refreshToken); // New token is different
        $this->assertSame(900, $result->expiresIn);
    }

    public function testItThrowsExceptionWhenRefreshTokenNotFound(): void
    {
        // Arrange
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn(null);

        // Assert
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        // Act
        $command = new RefreshTokenCommand('invalid_token');
        ($this->handler)($command);
    }

    public function testItThrowsExceptionWhenRefreshTokenIsExpired(): void
    {
        // Arrange
        $userId = UserId::random();
        $expiredToken = new RefreshToken(
            token: 'expired_token',
            userId: $userId,
            expiresAt: (new \DateTimeImmutable())->modify('-1 day')
        );

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($expiredToken);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('deleteByToken')
            ->with('expired_token');

        // Assert
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Refresh token has expired');

        // Act
        $command = new RefreshTokenCommand('expired_token');
        ($this->handler)($command);
    }

    public function testItThrowsExceptionWhenUserNotFound(): void
    {
        // Arrange
        $userId = UserId::random();
        $refreshToken = RefreshToken::generate($userId);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($refreshToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('User not found for refresh token');

        // Act
        $command = new RefreshTokenCommand('valid_token');
        ($this->handler)($command);
    }
}
