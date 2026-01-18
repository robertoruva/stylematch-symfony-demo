<?php

namespace App\Tests\Integration\Auth\Infrastructure\Security;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\UserId;
use Predis\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RedisRefreshTokenRepositoryTest extends KernelTestCase
{
    private RefreshTokenRepositoryInterface $repository;
    private ClientInterface $redis;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        $container = self::$kernel->getContainer();
        $this->repository = $container->get(RefreshTokenRepositoryInterface::class);
        $this->redis = $container->get(ClientInterface::class);

        // Clean Redis before each test
        $keys = $this->redis->keys('refresh_token:*');
        if (!empty($keys)) {
            $this->redis->del($keys);
        }

        $userTokenKeys = $this->redis->keys('user_tokens:*');
        if (!empty($userTokenKeys)) {
            $this->redis->del($userTokenKeys);
        }
    }

    public function testItCanSaveAndRetrieveRefreshToken(): void
    {
        // Arrange
        $userId = UserId::random();
        $refreshToken = RefreshToken::generate($userId, 3600); // 1 hour

        // Act
        $this->repository->save($refreshToken);

        // Assert
        $retrieved = $this->repository->findByToken($refreshToken->token());
        $this->assertNotNull($retrieved);
        $this->assertEquals($refreshToken->token(), $retrieved->token());
        $this->assertEquals($userId->value(), $retrieved->userId()->value());
    }

    public function testItReturnsNullForNonExistentToken(): void
    {
        // Arrange
        $nonExistentToken = 'non_existent_token_12345';

        // Act
        $result = $this->repository->findByToken($nonExistentToken);

        // Assert
        $this->assertNull($result);
    }

    public function testItCanDeleteTokenByValue(): void
    {
        // Arrange
        $userId = UserId::random();
        $refreshToken = RefreshToken::generate($userId, 3600);
        $this->repository->save($refreshToken);

        // Verify it exists
        $this->assertNotNull($this->repository->findByToken($refreshToken->token()));

        // Act
        $this->repository->deleteByToken($refreshToken->token());

        // Assert
        $this->assertNull($this->repository->findByToken($refreshToken->token()));
    }

    public function testItCanDeleteAllTokensForUser(): void
    {
        // Arrange
        $userId = UserId::random();
        $token1 = RefreshToken::generate($userId, 3600);
        $token2 = RefreshToken::generate($userId, 3600);
        $token3 = RefreshToken::generate($userId, 3600);

        $this->repository->save($token1);
        $this->repository->save($token2);
        $this->repository->save($token3);

        // Verify all exist
        $this->assertNotNull($this->repository->findByToken($token1->token()));
        $this->assertNotNull($this->repository->findByToken($token2->token()));
        $this->assertNotNull($this->repository->findByToken($token3->token()));

        // Act
        $this->repository->deleteAllByUserId($userId);

        // Assert
        $this->assertNull($this->repository->findByToken($token1->token()));
        $this->assertNull($this->repository->findByToken($token2->token()));
        $this->assertNull($this->repository->findByToken($token3->token()));
    }

    public function testItDoesNotDeleteTokensFromOtherUsers(): void
    {
        // Arrange
        $user1Id = UserId::random();
        $user2Id = UserId::random();

        $user1Token = RefreshToken::generate($user1Id, 3600);
        $user2Token = RefreshToken::generate($user2Id, 3600);

        $this->repository->save($user1Token);
        $this->repository->save($user2Token);

        // Act - Delete only user1's tokens
        $this->repository->deleteAllByUserId($user1Id);

        // Assert
        $this->assertNull($this->repository->findByToken($user1Token->token()));
        $this->assertNotNull($this->repository->findByToken($user2Token->token()), 'User2 token should still exist');
    }

    public function testItPersistsTokenExpirationCorrectly(): void
    {
        // Arrange
        $userId = UserId::random();
        $ttl = 7200; // 2 hours
        $refreshToken = RefreshToken::generate($userId, $ttl);

        // Act
        $this->repository->save($refreshToken);
        $retrieved = $this->repository->findByToken($refreshToken->token());

        // Assert
        $this->assertNotNull($retrieved);
        $this->assertFalse($retrieved->isExpired(), 'Token should not be expired immediately after creation');

        // Check that expiration is set in the future
        $now = new \DateTimeImmutable();
        $this->assertGreaterThan($now, $retrieved->expiresAt());
    }

    public function testItCanHandleMultipleTokensForSameUser(): void
    {
        // Arrange
        $userId = UserId::random();
        $token1 = RefreshToken::generate($userId, 3600);
        $token2 = RefreshToken::generate($userId, 3600);
        $token3 = RefreshToken::generate($userId, 3600);

        // Act
        $this->repository->save($token1);
        $this->repository->save($token2);
        $this->repository->save($token3);

        // Assert - All tokens should be retrievable
        $retrieved1 = $this->repository->findByToken($token1->token());
        $retrieved2 = $this->repository->findByToken($token2->token());
        $retrieved3 = $this->repository->findByToken($token3->token());

        $this->assertNotNull($retrieved1);
        $this->assertNotNull($retrieved2);
        $this->assertNotNull($retrieved3);

        // All should belong to same user
        $this->assertEquals($userId->value(), $retrieved1->userId()->value());
        $this->assertEquals($userId->value(), $retrieved2->userId()->value());
        $this->assertEquals($userId->value(), $retrieved3->userId()->value());

        // But have different token values
        $this->assertNotEquals($token1->token(), $token2->token());
        $this->assertNotEquals($token2->token(), $token3->token());
        $this->assertNotEquals($token1->token(), $token3->token());
    }

    public function testItStoresTokenInRedisWithCorrectKey(): void
    {
        // Arrange
        $userId = UserId::random();
        $refreshToken = RefreshToken::generate($userId, 3600);

        // Act
        $this->repository->save($refreshToken);

        // Assert - Check Redis directly
        $redisKey = 'refresh_token:'.$refreshToken->token();
        $exists = $this->redis->exists($redisKey);
        $this->assertEquals(1, $exists, 'Token should exist in Redis with correct key');

        // Check the stored data structure
        $storedData = $this->redis->get($redisKey);
        $this->assertNotEmpty($storedData);

        $decoded = json_decode($storedData, true);
        $this->assertArrayHasKey('user_id', $decoded);
        $this->assertArrayHasKey('expires_at', $decoded);
        $this->assertEquals($userId->value(), $decoded['user_id']);
    }

    public function testItSetsRedisTTLForAutoExpiration(): void
    {
        // Arrange
        $userId = UserId::random();
        $ttl = 3600; // 1 hour
        $refreshToken = RefreshToken::generate($userId, $ttl);

        // Act
        $this->repository->save($refreshToken);

        // Assert - Check Redis TTL
        $redisKey = 'refresh_token:'.$refreshToken->token();
        $redisTTL = $this->redis->ttl($redisKey);

        // Redis TTL should be close to our TTL (within 10 seconds tolerance for test execution time)
        $this->assertGreaterThan($ttl - 10, $redisTTL, 'Redis TTL should be approximately equal to token TTL');
        $this->assertLessThanOrEqual($ttl, $redisTTL, 'Redis TTL should not exceed token TTL');
    }
}
