<?php

namespace App\Tests\Unit\Auth\Application\Command\RegisterUser;

use App\Auth\Application\Command\RegisterUser\RegisterUserCommand;
use App\Auth\Application\Command\RegisterUser\RegisterUserHandler;
use App\Auth\Application\Message\MessageBusInterface;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Entity\UserFactory;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RegisterUserHandlerTest extends TestCase
{
    #[Test]
    public function testItRegistersANewUser(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $token = $this->createMock(TokenGeneratorInterface::class);
        $messageBus = $this->createMock(MessageBusInterface::class);

        $repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return 'new@test.com' === $user->getEmail()->value();
            }));

        $token->expects($this->once())
            ->method('generate')
            ->with($this->isInstanceOf(User::class))
            ->willReturn('fake-token-123');

        $messageBus->expects($this->once())
            ->method('publish')
            ->with(
                $this->anything(),
                'user_registered'
            );

        $handler = new RegisterUserHandler($repo, $token, $messageBus);

        $command = new RegisterUserCommand(
            'Test User',
            'new@test.com',
            'password123'
        );

        $result = $handler($command);

        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('new@test.com', $result->email);
        $this->assertEquals('fake-token-123', $result->token);
    }

    #[Test]
    public function testItThrowsExceptionIfEmailAlreadyExists(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $token = $this->createMock(TokenGeneratorInterface::class);
        $messageBus = $this->createMock(MessageBusInterface::class);

        $repo->method('findByEmail')
            ->willReturn(
                UserFactory::create(
                    UserId::random(),
                    'Existing User',
                    new Email('existing@test.com'),
                    PasswordHash::fromPlainText('pass11111111'),
                    new \DateTimeImmutable(),
                    null
                )
            );

        $handler = new RegisterUserHandler($repo, $token, $messageBus);

        $this->expectException(UserAlreadyExistsException::class);

        $command = new RegisterUserCommand(
            'Existing User',
            'existing@test.com',
            'Passsss12345'
        );

        $handler($command);
    }
}
