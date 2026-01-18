<?php

namespace App\Tests\Integration\Auth\Infrastructure\Persistence;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        $container = self::$kernel->getContainer();
        $this->repository = $container->get(UserRepositoryInterface::class);

        // Clean database before each test
        $entityManager = $container->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    }

    public function testItCanSaveAndRetrieveUserById(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'John Doe',
            email: new Email('john@example.com'),
            password: PasswordHash::fromPlainText('password123')
        );

        // Act
        $this->repository->save($user);

        // Assert - Retrieve by ID
        $retrievedUser = $this->repository->findById($user->getId());

        $this->assertNotNull($retrievedUser);
        $this->assertEquals($user->getId()->value(), $retrievedUser->getId()->value());
        $this->assertEquals('john@example.com', $retrievedUser->getEmail()->value());
        $this->assertEquals('John Doe', $retrievedUser->getName());
    }

    public function testItCanRetrieveUserByEmail(): void
    {
        // Arrange
        $email = new Email('jane@example.com');
        $user = User::register(
            id: UserId::random(),
            name: 'Jane Doe',
            email: $email,
            password: PasswordHash::fromPlainText('password456')
        );

        $this->repository->save($user);

        // Act
        $retrievedUser = $this->repository->findByEmail($email);

        // Assert
        $this->assertNotNull($retrievedUser);
        $this->assertEquals($user->getId()->value(), $retrievedUser->getId()->value());
        $this->assertEquals('jane@example.com', $retrievedUser->getEmail()->value());
    }

    public function testItReturnsNullWhenUserNotFoundById(): void
    {
        // Arrange
        $nonExistentId = UserId::random();

        // Act
        $result = $this->repository->findById($nonExistentId);

        // Assert
        $this->assertNull($result);
    }

    public function testItReturnsNullWhenUserNotFoundByEmail(): void
    {
        // Arrange
        $nonExistentEmail = new Email('nonexistent@example.com');

        // Act
        $result = $this->repository->findByEmail($nonExistentEmail);

        // Assert
        $this->assertNull($result);
    }

    public function testItChecksIfEmailExists(): void
    {
        // Arrange
        $existingEmail = new Email('existing@example.com');
        $user = User::register(
            id: UserId::random(),
            name: 'Existing User',
            email: $existingEmail,
            password: PasswordHash::fromPlainText('password789')
        );

        $this->repository->save($user);

        // Act & Assert - Email exists
        $this->assertTrue($this->repository->existsByEmail($existingEmail));

        // Act & Assert - Email does not exist
        $nonExistentEmail = new Email('notfound@example.com');
        $this->assertFalse($this->repository->existsByEmail($nonExistentEmail));
    }

    public function testItCanDeleteUser(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'To Be Deleted',
            email: new Email('delete@example.com'),
            password: PasswordHash::fromPlainText('password')
        );

        $this->repository->save($user);

        // Verify user exists
        $this->assertNotNull($this->repository->findById($user->getId()));

        // Act
        $this->repository->delete($user);

        // Assert
        $this->assertNull($this->repository->findById($user->getId()));
    }

    public function testItPersistsPasswordHashCorrectly(): void
    {
        // Arrange
        $plainPassword = 'supersecret123';
        $passwordHash = PasswordHash::fromPlainText($plainPassword);

        $user = User::register(
            id: UserId::random(),
            name: 'Password Test User',
            email: new Email('password@example.com'),
            password: $passwordHash
        );

        // Act
        $this->repository->save($user);

        // Assert
        $retrievedUser = $this->repository->findById($user->getId());
        $this->assertNotNull($retrievedUser);

        // Password should be hashed (not plain text)
        $this->assertNotEquals($plainPassword, $retrievedUser->getPassword()->value());

        // Verify with password_verify
        $this->assertTrue(
            password_verify($plainPassword, $retrievedUser->getPassword()->value())
        );
    }

    public function testItCanSaveMultipleUsersWithUniqueEmails(): void
    {
        // Arrange & Act
        $user1 = User::register(
            id: UserId::random(),
            name: 'User One',
            email: new Email('user1@example.com'),
            password: PasswordHash::fromPlainText('password1')
        );
        $this->repository->save($user1);

        $user2 = User::register(
            id: UserId::random(),
            name: 'User Two',
            email: new Email('user2@example.com'),
            password: PasswordHash::fromPlainText('password2')
        );
        $this->repository->save($user2);

        // Assert
        $retrievedUser1 = $this->repository->findByEmail(new Email('user1@example.com'));
        $retrievedUser2 = $this->repository->findByEmail(new Email('user2@example.com'));

        $this->assertNotNull($retrievedUser1);
        $this->assertNotNull($retrievedUser2);
        $this->assertNotEquals($retrievedUser1->getId()->value(), $retrievedUser2->getId()->value());
    }
}
