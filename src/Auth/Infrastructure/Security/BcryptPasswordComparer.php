<?php

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Service\PasswordComparerInterface;
use App\Auth\Domain\ValueObject\PasswordHash;

final class BcryptPasswordComparer implements PasswordComparerInterface
{
    public function verify(string $plainPassword, PasswordHash $hash): bool
    {
        return password_verify($plainPassword, $hash->value());
    }
}
