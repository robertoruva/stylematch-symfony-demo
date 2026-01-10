<?php

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\UserId;
use Predis\ClientInterface as RedisClient;

final readonly class RedisRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private const PREFIX = 'refresh_token:';
    private const USER_PREFIX = 'user_tokens:';

    public function __construct(
        private readonly RedisClient $redis
    ) {}

    public function save(RefreshToken $token): void
    {
        $key = self::PREFIX . $token->token();
        $ttl = $token->expiresAt()->getTimestamp() - time();

        // Store token data as JSON
        $data = json_encode([
            'user_id' => $token->userId()->value(),
            'expires_at' => $token->expiresAt()->format('Y-m-d H:i:s'),
            'created_at' => $token->createdAt()->format('Y-m-d H:i:s'),
        ]);

        // SETEX: Set with expiration
        $this->redis->setex($key, max($ttl, 1), $data);

        // Add to user's token set
        $userKey = self::USER_PREFIX . $token->userId()->value();
        $this->redis->sadd($userKey, [$token->token()]);
        
        // Set expiration on user's token set
        $this->redis->expire($userKey, max($ttl, 1));
    }

    public function findByToken(string $token): ?RefreshToken
    {
        $token = trim($token);
        $key = self::PREFIX . $token;
        $data = $this->redis->get($key);

        if ($data === null || $data === false) {
            return null;
        }

        try {
            $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            return new RefreshToken(
                token: $token,
                userId: UserId::fromString($decoded['user_id']),
                expiresAt: new \DateTimeImmutable($decoded['expires_at'])
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteByToken(string $token): void
    {
        error_log("=== DELETE TOKEN START ===");
        error_log("Token to delete: " . substr($token, 0, 32) . "...");
        
        $token = trim($token);
        $key = self::PREFIX . $token;
        error_log("Redis key: " . $key);
        
        try {
            // Get user_id before deleting to remove from user set
            error_log("Attempting GET: " . $key);
            $data = $this->redis->get($key);
            error_log("GET result: " . ($data ? "Found" : "Not found"));
            
            if ($data !== null && $data !== false) {
                error_log("Data found, decoding JSON");
                try {
                    $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                    $userKey = self::USER_PREFIX . $decoded['user_id'];
                    
                    error_log("User key: " . $userKey);
                    error_log("Removing token from user set");
                    
                    // Remove token from user's set
                    $result = $this->redis->srem($userKey, $token);
                    error_log("SREM result: " . $result);
                    
                } catch (\Exception $e) {
                    error_log("JSON decode error: " . $e->getMessage());
                    // Continue with deletion even if user set update fails
                }
            } else {
                error_log("Data is null or false, skipping user set removal");
            }

            // Delete the token
            error_log("Attempting DEL: " . $key);
            $result = $this->redis->del([$key]);
            error_log("DEL result: " . $result);
            error_log("=== DELETE TOKEN END ===");
            
        } catch (\Exception $e) {
            error_log("CRITICAL ERROR in deleteByToken: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function deleteAllByUserId(UserId $userId): void
    {
        $userKey = self::USER_PREFIX . $userId->value();
        
        // Get all tokens for user
        $tokens = $this->redis->smembers($userKey);

        if (!empty($tokens)) {
            // Delete each token
            $keys = array_map(
                fn(string $token) => self::PREFIX . $token,
                $tokens
            );
            
            $this->redis->del($keys);
        }

        // Delete user's token set
        $this->redis->del([$userKey]);
    }
}
