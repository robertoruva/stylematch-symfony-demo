<?php

namespace App\Auth\Domain\Service;

use App\Auth\Domain\Entity\User;

interface TokenGeneratorInterface
{
    public function generate(User $user): string;
}
