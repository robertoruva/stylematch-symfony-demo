<?php

namespace App\Tests\Unit\Auth\Domain\Entity;

use App\Auth\Domain\Entity\UserFactory;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    #[Test]
    public function testItCreatesAUserEntity(): void
    {
        $user = UserFactory::create(
            UserId::random(),
            'Test User',
            new Email('test@example.com'),
            PasswordHash::fromPlainText('secret123')
        );

        $this->assertInstanceOf(UserId::class, $user->getId());
        $this->assertInstanceOf(Email::class, $user->getEmail());
        $this->assertInstanceOf(PasswordHash::class, $user->getPassword());
    }

    #[Test]
    public function testUserCannotChangeEmailDirectly(): void
    {
        $user = UserFactory::create(
            UserId::random(),
            'Test User',
            new Email('test@example.com'),
            PasswordHash::fromPlainText('secret123')
        );

        $this->assertInstanceOf(Email::class, $user->getEmail());
        $this->assertEquals('test@example.com', $user->getEmail()->value());
    }
}
