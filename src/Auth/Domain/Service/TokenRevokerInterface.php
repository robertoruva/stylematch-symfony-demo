<?php

namespace App\Auth\Domain\Service;

use App\Auth\Domain\ValueObject\TokenString;
use App\Auth\Domain\ValueObject\UserId;

interface TokenRevokerInterface
{
    /**
     * Revoke all user tokens.
     */
    public function revokeAllTokensForUser(UserId $userId): void;

    /**
     * Revoke a specific token
     * Idempotent: does not throw an exception if the token does not existIdempotent: does not throw an exception if the token does not exist.
     */
    public function revokeToken(TokenString $plainToken): void;

    /**
     * Check if a token is revoked.
     */
    public function isTokenRevoked(TokenString $plainToken): bool;

    /**
     * Check if a token exists and is valid.
     */
    public function isTokenValid(TokenString $plainToken): bool;
}
