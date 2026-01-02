<?php

namespace App\Auth\Domain\Repository;

use App\Auth\Domain\Entity\User;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;
}
