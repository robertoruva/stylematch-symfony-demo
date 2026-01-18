<?php

namespace App\Tests\Integration\Auth\Infrastructure\Security;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class JWTTokenGeneratorTest extends KernelTestCase
{
    private TokenGeneratorInterface $tokenGenerator;
    private JWTTokenManagerInterface $jwtManager;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        $container = self::$kernel->getContainer();
        $this->tokenGenerator = $container->get(TokenGeneratorInterface::class);
        $this->jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');
    }

    public function testItGeneratesValidJWTToken(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'Test User',
            email: new Email('test@example.com'),
            password: PasswordHash::fromPlainText('password123')
        );

        // Act
        $token = $this->tokenGenerator->generate($user);

        // Assert
        $this->assertNotEmpty($token->value());
        $this->assertIsString($token->value());

        // JWT format: header.payload.signature
        $parts = explode('.', $token->value());
        $this->assertCount(3, $parts, 'JWT should have 3 parts separated by dots');
    }

    public function testItIncludesUserDataInTokenPayload(): void
    {
        // Arrange
        $userId = UserId::random();
        $user = User::register(
            id: $userId,
            name: 'John Doe',
            email: new Email('john@example.com'),
            password: PasswordHash::fromPlainText('password456')
        );

        // Act
        $token = $this->tokenGenerator->generate($user);

        // Decode the JWT to inspect payload
        $payload = $this->jwtManager->parse($token->value());

        // Assert
        $this->assertArrayHasKey('user_id', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('name', $payload);

        $this->assertEquals($userId->value(), $payload['user_id']);
        $this->assertEquals('john@example.com', $payload['email']);
        $this->assertEquals('John Doe', $payload['name']);
    }

    public function testItIncludesStandardJWTClaims(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'Jane Doe',
            email: new Email('jane@example.com'),
            password: PasswordHash::fromPlainText('password789')
        );

        // Act
        $token = $this->tokenGenerator->generate($user);
        $payload = $this->jwtManager->parse($token->value());

        // Assert - Standard JWT claims
        $this->assertArrayHasKey('iat', $payload, 'Should have "issued at" claim');
        $this->assertArrayHasKey('exp', $payload, 'Should have "expiration" claim');

        // iat should be a recent timestamp
        $this->assertLessThanOrEqual(time(), $payload['iat']);
        $this->assertGreaterThan(time() - 60, $payload['iat'], 'iat should be within last minute');

        // exp should be in the future
        $this->assertGreaterThan(time(), $payload['exp'], 'Token should not be expired');
    }

    public function testItGeneratesUniqueTokensForSameUser(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'Same User',
            email: new Email('same@example.com'),
            password: PasswordHash::fromPlainText('password')
        );

        // Act - Generate multiple tokens
        $token1 = $this->tokenGenerator->generate($user);
        sleep(1); // Ensure different iat timestamp
        $token2 = $this->tokenGenerator->generate($user);

        // Assert
        $this->assertNotEquals($token1->value(), $token2->value(), 'Tokens should be unique even for same user');

        // Both tokens should be valid
        $payload1 = $this->jwtManager->parse($token1->value());
        $payload2 = $this->jwtManager->parse($token2->value());

        $this->assertEquals($user->getId()->value(), $payload1['user_id']);
        $this->assertEquals($user->getId()->value(), $payload2['user_id']);
        $this->assertNotEquals($payload1['iat'], $payload2['iat'], 'Tokens should have different iat');
    }

    public function testItGeneratesTokenThatCanBeVerified(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'Verifiable User',
            email: new Email('verify@example.com'),
            password: PasswordHash::fromPlainText('password')
        );

        // Act
        $token = $this->tokenGenerator->generate($user);

        // Assert - Token can be parsed without exceptions
        try {
            $payload = $this->jwtManager->parse($token->value());
            $this->assertIsArray($payload);
        } catch (\Exception $e) {
            $this->fail('Token should be verifiable: '.$e->getMessage());
        }
    }

    public function testTokenExpirationIsSetCorrectly(): void
    {
        // Arrange
        $user = User::register(
            id: UserId::random(),
            name: 'Expiry Test User',
            email: new Email('expiry@example.com'),
            password: PasswordHash::fromPlainText('password')
        );

        // Act
        $token = $this->tokenGenerator->generate($user);
        $payload = $this->jwtManager->parse($token->value());

        // Assert
        $ttl = $payload['exp'] - $payload['iat'];

        // JWT_TTL can vary by environment (900 in prod, 3600 in test is common)
        // Just verify it's a reasonable value (between 15 minutes and 24 hours)
        $this->assertGreaterThanOrEqual(900, $ttl, 'Token TTL should be at least 15 minutes');
        $this->assertLessThanOrEqual(86400, $ttl, 'Token TTL should not exceed 24 hours');
    }
}
