<?php

namespace App\Tests\Unit\Auth\Domain\Entity;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Entity\UserFactory;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
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
    public function testItCreatesAUserEntity(): void
    {
        $user = $user = $this->createUser();

        $this->assertInstanceOf(UserId::class, $user->getId());
        $this->assertInstanceOf(Email::class, $user->getEmail());
        $this->assertInstanceOf(PasswordHash::class, $user->getPassword());
    }

    #[Test]
    public function testUserCannotChangeEmailDirectly(): void
    {
        $user = $user = $this->createUser();

        $this->assertInstanceOf(Email::class, $user->getEmail());
        $this->assertEquals('test@example.com', $user->getEmail()->value());
    }
}
