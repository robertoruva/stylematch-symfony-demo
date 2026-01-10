<?php

namespace App\Auth\Domain\Repository;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\UserId;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $token): void;

    public function findByToken(string $token): ?RefreshToken;

    public function deleteByToken(string $token): void;

    public function deleteAllByUserId(UserId $userId): void;
}
