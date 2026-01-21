<?php

namespace App\Tests\Unit\Auth\Application\Command\LogoutUser;

use App\Auth\Application\Command\LogoutUser\LogoutUserCommand;
use App\Auth\Application\Command\LogoutUser\LogoutUserHandler;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Token;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogoutUserHandlerTest extends TestCase
{
    #[Test]
    public function testItLogsOutUserAndDeletesAllRefreshTokens(): void
    {
        // Arrange
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $jwtToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiMTIzIn0.abc';
        $userId = UserId::random();

        // Mock: JWT token verification returns userId
        $tokenGenerator->expects($this->once())
            ->method('verify')
            ->with($this->callback(function (Token $token) use ($jwtToken) {
                return $token->value() === $jwtToken;
            }))
            ->willReturn($userId);

        // Mock: All refresh tokens for user should be deleted
        $refreshTokenRepository->expects($this->once())
            ->method('deleteAllByUserId')
            ->with($this->callback(function (UserId $id) use ($userId) {
                return $id->value() === $userId->value();
            }));

        $handler = new LogoutUserHandler($tokenGenerator, $refreshTokenRepository, $logger);

        // Act
        $command = new LogoutUserCommand($jwtToken);
        $handler($command);

        // Assert: No exception thrown, method calls verified by mocks
        $this->assertTrue(true);
    }

    #[Test]
    public function testItHandlesInvalidTokenGracefully(): void
    {
        // Arrange
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $invalidToken = 'invalid_or_expired_jwt';

        // Mock: JWT verification fails (returns null)
        $tokenGenerator->expects($this->once())
            ->method('verify')
            ->willReturn(null);

        // Mock: No deletion should happen since token is invalid
        $refreshTokenRepository->expects($this->never())
            ->method('deleteAllByUserId');

        $handler = new LogoutUserHandler($tokenGenerator, $refreshTokenRepository, $logger);

        // Act
        $command = new LogoutUserCommand($invalidToken);
        $handler($command);

        // Assert: No exception thrown (logout is idempotent)
        $this->assertTrue(true);
    }
}
