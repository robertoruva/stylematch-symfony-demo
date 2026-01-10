<?php

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\ValueObject\UserId;

final class RefreshToken
{
    private string $token;
    private UserId $userId;
    private \DateTimeImmutable $expiresAt;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $token,
        UserId $userId,
        \DateTimeImmutable $expiresAt
    ) {
        $this->token = $token;
        $this->userId = $userId;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function token(): string
    {
        return $this->token;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public static function generate(
        UserId $userId,
        int $ttlSeconds = 604800  // 7 days default
    ): self {
        return new self(
            token: bin2hex(random_bytes(32)),  // 64 character hex string
            userId: $userId,
            expiresAt: (new \DateTimeImmutable())->modify("+{$ttlSeconds} seconds")
        );
    }
}
