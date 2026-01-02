<?php

namespace App\Tests\Unit\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\ValueObject\TokenString;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TokenStringTest extends TestCase
{
    #[Test]
    public function it_extracts_token_from_sanctum_header_format(): void
    {
        $token = new TokenString('1|abcd1234xyz');

        $this->assertEquals('1|abcd1234xyz', $token->value());
    }

    #[Test]
    public function it_extracts_token_even_if_the_id_is_long(): void
    {
        $token = new TokenString('999|token-final-777');

        $this->assertEquals('999|token-final-777', $token->value());
    }

    #[Test]
    public function it_throws_exception_if_token_is_empty(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token cannot be empty');

        new TokenString('');
    }

    #[Test]
    public function it_throws_exception_if_token_part_after_separator_is_missing(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid bearer token format');

        new TokenString('1|');
    }
}
