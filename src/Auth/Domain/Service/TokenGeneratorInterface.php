<?php

namespace App\Auth\Domain\Service;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\ValueObject\Token;
use App\Auth\Domain\ValueObject\UserId;

interface TokenGeneratorInterface
{
    public function generate(User $user): Token;

    public function verify(Token $token): ?UserId;
}
