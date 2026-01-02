<?php

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;

final class UserFactory
{
    public static function create(
        UserId $id,
        string $name,
        Email $email,
        PasswordHash $plainPassword
    ): User {
        $name = trim($name);

        if ('' === $name) {
            throw new \DomainException('User name cannot be empty.');
        }

        return new User(
            $id,
            $name,
            $email,
            $plainPassword,
        );
    }

    /**
     * Creates a dummy user for domain tests.
     */
    public static function fake(): User
    {
        $faker = \Faker\Factory::create();

        return self::create(
            UserId::random(),
            $faker->name(),
            new Email($faker->unique()->safeEmail()),
            PasswordHash::fromPlainText('Password123!')
        );
    }
}
