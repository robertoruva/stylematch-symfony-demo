<?php

namespace Tests\Unit\Auth\Domain\ValueObjects;

use App\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserIdTest extends TestCase
{
    #[Test]
    public function testItGeneratesAUuid(): void
    {
        $id = UserId::random();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-fA-F-]{36}$/',
            $id->value()
        );
    }

    #[Test]
    public function testTwoUserIdsAreNotEqual(): void
    {
        $id1 = UserId::random();
        $id2 = UserId::random();

        $this->assertNotEquals($id1->value(), $id2->value());
    }
}
