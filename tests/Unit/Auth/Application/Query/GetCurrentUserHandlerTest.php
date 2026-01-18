<?php


namespace App\Tests\Unit\Auth\Application\Query;

use App\Auth\Application\Query\GetCurrentUser\GetCurrentUserHandler;
use App\Auth\Application\Query\GetCurrentUser\GetCurrentUserQuery;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserNotFoundException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class GetCurrentUserHandlerTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private GetCurrentUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new GetCurrentUserHandler($this->userRepository);
    }

    public function test_it_returns_user_dto_when_user_exists(): void
    {
        // Arrange
        $userId = UserId::random();
        $user = User::register(
            id: $userId,
            name: 'Test User',
            email: new Email('test@example.com'),
            password: PasswordHash::fromPlainText('password123')
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(function (UserId $id) use ($userId) {
                return $id->value() === $userId->value();
            }))
            ->willReturn($user);

        // Act
        $query = new GetCurrentUserQuery($userId->value());
        $result = ($this->handler)($query);

        // Assert
        $this->assertSame($userId->value(), $result->id);
        $this->assertSame('test@example.com', $result->email);
        $this->assertNotNull($result->createdAt);
    }

    public function test_it_throws_exception_when_user_not_found(): void
    {
        // Arrange
        $userId = UserId::random();

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with ID {$userId->value()} not found");

        // Act
        $query = new GetCurrentUserQuery($userId->value());
        ($this->handler)($query);
    }
}
