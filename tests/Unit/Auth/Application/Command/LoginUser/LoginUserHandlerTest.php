<?php

namespace App\Tests\Unit\Auth\Application\Command\LoginUser;

use App\Auth\Application\Command\LoginUser\LoginUserCommand;
use App\Auth\Application\Command\LoginUser\LoginUserHandler;
use App\Auth\Domain\Entity\UserFactory;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Domain\Exception\UserNotFoundException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LoginUserHandlerTest extends TestCase
{
    #[Test]
    public function testItLogsInAUser(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);

        $user = UserFactory::create(
            UserId::random(),
            'Test User',
            new Email('test@test.com'),
            PasswordHash::fromPlainText('password123')
        );

        $repo->method('findByEmail')
            ->with($this->callback(fn ($email) => $email instanceof Email))
            ->willReturn($user);

        $tokenGenerator->method('generate')
            ->willReturn('fake-token-123');

        $tokenGenerator->method('generate')
            ->willReturn('fake-token');

        $handler = new LoginUserHandler($repo, $tokenGenerator);

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
        $user = UserFactory::create(
            UserId::random(),
            'Test User',
            new Email('test@test.com'),
            PasswordHash::fromPlainText('password123')
        );

        $repo = $this->createMock(UserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);

        $repo->method('findByEmail')
            ->with($this->callback(fn ($email) => $email instanceof Email))
            ->willReturn($user);

        $handler = new LoginUserHandler($repo, $tokenGenerator);
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

        $handler = new LoginUserHandler($repo, $tokenGenerator);

        $this->expectException(UserNotFoundException::class);

        $command = new LoginUserCommand('missing@test.com', 'whatever');

        $handler($command);
    }

    #[Test]
    public function testItLogsInAUserGenerateToken(): void
    {
        $user = UserFactory::create(
            UserId::random(),
            'Test User',
            new Email('test@test.com'),
            PasswordHash::fromPlainText('password123')
        );

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

        $handler = new LoginUserHandler($repo, $tokenGenerator);

        $command = new LoginUserCommand('test@test.com', 'password123');

        $result = $handler($command);

        $this->assertSame($user->getId()->value(), $result->id);
        $this->assertSame('Test User', $result->name);
        $this->assertSame('test@test.com', $result->email);
        $this->assertSame('fake-token-123', $result->token);
    }
}
