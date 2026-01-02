<?php

namespace App\Tests\Unit\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\InvalidEmailException;
use App\Auth\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    #[Test]
    public function testItCreatesAValidEmail(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->value());
    }

    #[Test]
    public function testItThrowsExceptionForInvalidEmail(): void
    {
        $this->expectException(InvalidEmailException::class);
        new Email('not-an-email');
    }

    #[Test]
    public function testTwoEmailsAreEqual(): void
    {
        $e1 = new Email('user@test.com');
        $e2 = new Email('user@test.com');

        $this->assertTrue($e1->equals($e2));
    }

    #[Test]
    public function testTwoEmailsAreNotEqual(): void
    {
        $e1 = new Email('user1@test.com');
        $e2 = new Email('user2@test.com');

        $this->assertFalse($e1->equals($e2));
    }
}
