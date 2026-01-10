<?php

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;

final class User
{
    private string $id;

    private string $name;

    private Email $email;

    private PasswordHash $password;

    private \DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        UserId $id,
        string $name,
        Email $email,
        PasswordHash $password,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id->value();
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function register(
        UserId $id,
        string $name,
        Email $email,
        PasswordHash $password
    ): self {
        return new self(
            $id,
            $name,
            $email,
            $password,
            new \DateTimeImmutable(),
        );
    }

    public function getId(): UserId
    {
        return new UserId($this->id);
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changePassword(PasswordHash $newPassword): void
    {
        $this->password = $newPassword;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateProfile(string $name, Email $email): void
    {
        $this->name = $name;
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
