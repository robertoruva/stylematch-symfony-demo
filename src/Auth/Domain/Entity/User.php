<?php

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;

final class User
{
    private UserId $id;

    private string $name;

    private Email $email;

    private PasswordHash $password;

    public function __construct(UserId $id, string $name, Email $email, PasswordHash $password)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): PasswordHash
    {
        return $this->password;
    }

    public function changePassword(PasswordHash $newPassword): void
    {
        $this->password = $newPassword;
    }

    public function updateProfile(string $name, Email $email): void
    {
        $this->name = $name;
        $this->email = $email;
    }

    public static function register(
        UserId $id,
        string $name,
        Email $email,
        PasswordHash $password
    ): self {
        return new self($id, $name, $email, $password);
    }
}
