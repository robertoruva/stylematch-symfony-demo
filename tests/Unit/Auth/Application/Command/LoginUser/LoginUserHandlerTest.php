<?php

namespace App\Tests\Unit\Auth\Application\Command\LoginUser;

use App\Auth\Application\Command\LoginUser\LoginUserCommand;
use App\Auth\Application\Command\LoginUser\LoginUserHandler;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Entity\UserFactory;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Domain\Exception\UserNotFoundException;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\PasswordComparerInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginUserHandlerTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;
    private MockObject&PasswordComparerInterface $passwordComparer;
    private MockObject&TokenGeneratorInterface $tokenGenerator;
    private MockObject&RefreshTokenRepositoryInterface $refreshTokenRepository;

    protected function setUp(): void 
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordComparer = $this->createMock(PasswordComparerInterface::class);
        $this->tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
    }

    // ============================================
    // Test Data Factories
    // ============================================
    private function createUser(
        ?string $email = null,
        ?string $password = null,
        ?string $name = null
    ): User {
        return UserFactory::create(
            UserId::random(),
            $name ?? 'Test User',
            new Email($email ?? 'test@example.com'),
            PasswordHash::fromPlainText($password ?? 'password123'),
            new \DateTimeImmutable(),
            null
        );
    }

    #[Test]
    public function testItLogsInAUser(): void
    {
        $user = $user = $this->createUser();

        $this->userRepository
            ->method('findByEmail')
            ->with($this->callback(fn ($email) => $email instanceof Email))
            ->willReturn($user);

        $this->tokenGenerator
            ->method('generate')
            ->willReturn('fake-token-123');

        $this->tokenGenerator
        ->method('generate')
            ->willReturn('fake-token');

        $handler = new LoginUserHandler(
            $this->userRepository,
            $this->passwordComparer,
            $this->tokenGenerator,
            $this->refreshTokenRepository
        );

        $command = new LoginUserCommand(
            'test@test.com',
            'password123'
        );

        $result = $handler($command);
        $this->assertNotEmpty($result->token);
    }

    #[Test]
    public function testItThrowsInvalidCredentialsIfPasswordDoesNotMatch(): void
    {
        $user = $user = $this->createUser();

        $repo = $this->createMock(UserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);

        $repo->method('findByEmail')
            ->with($this->callback(fn ($email) => $email instanceof Email))
            ->willReturn($user);

        $handler = new LoginUserHandler(
            $this->userRepository,
            $this->passwordComparer,
            $this->tokenGenerator,
            $this->refreshTokenRepository
        );

        $this->expectException(InvalidCredentialsException::class);

        $command = new LoginUserCommand('missing@test.com', '1234');

        $handler($command);
    }

    #[Test]
    public function testItThrowsExceptionIfUserNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);

        $repo->method('findByEmail')
            ->with($this->callback(
                fn (Email $email) => 'missing@test.com' === $email->value()
            ))
            ->willReturn(null);

        $handler = new LoginUserHandler(
            $this->userRepository,
            $this->passwordComparer,
            $this->tokenGenerator,
            $this->refreshTokenRepository
        );

        $this->expectException(UserNotFoundException::class);

        $command = new LoginUserCommand('missing@test.com', 'whatever');

        $handler($command);
    }

    #[Test]
    public function testItLogsInAUserGenerateToken(): void
    {
        $user = $user = $this->createUser();

        $repo = $this->createMock(UserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);

        $repo->method('findByEmail')
            ->with($this->callback(
                fn (Email $email) => 'test@test.com' === $email->value()
            ))
            ->willReturn($user);

        $tokenGenerator->expects($this->once())
            ->method('generate')
            ->with($user)
            ->willReturn('fake-token-123');

        $handler = new LoginUserHandler(
            $this->userRepository,
            $this->passwordComparer,
            $this->tokenGenerator,
            $this->refreshTokenRepository
        );

        $command = new LoginUserCommand('test@test.com', 'password123');

        $result = $handler($command);

        $this->assertSame($user->getId()->value(), $result->id);
        $this->assertSame('Test User', $result->name);
        $this->assertSame('test@test.com', $result->email);
        $this->assertSame('fake-token-123', $result->token);
    }
}
