<?php

namespace App\Tests\Unit\Auth\Domain\ValueObject;

use App\Auth\Domain\ValueObject\PasswordHash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PasswordHashTest extends TestCase
{
    #[Test]
    public function testItHashesAPlainPassword(): void
    {
        $hash = PasswordHash::fromPlainText('secret123');

        $this->assertNotEquals('secret123', $hash->value());
        $this->assertTrue(password_verify('secret123', $hash->value()));
    }

    #[Test]
    public function testTwoHashesFromSamePasswordAreNotEqual(): void
    {
        $h1 = PasswordHash::fromPlainText('secret123');
        $h2 = PasswordHash::fromPlainText('secret123');

        $this->assertNotEquals($h1->value(), $h2->value());
    }
}
