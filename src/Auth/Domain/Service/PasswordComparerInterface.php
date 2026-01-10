<?php

namespace App\Auth\Domain\Service;

use App\Auth\Domain\ValueObject\PasswordHash;

interface PasswordComparerInterface
{
    public function verify(string $plainPassword, PasswordHash $hash): bool;
}
