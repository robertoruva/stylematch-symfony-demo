<?php

namespace App\Auth\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Infrastructure Adapter: DTO that adapts Domain User data to Symfony Security contracts.
 * This is a simple data holder with NO business logic and NO Domain dependencies.
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $password,
        private readonly string $name,
        private readonly array $roles = ['ROLE_USER']
    ) {
    }

    /**
     * Symfony Security UserInterface implementation.
     * Uses user ID as identifier (for JWT token subject).
     */
    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    /**
     * Symfony Security PasswordAuthenticatedUserInterface implementation.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Symfony Security UserInterface implementation.
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Symfony Security UserInterface implementation.
     */
    public function eraseCredentials(): void
    {
        // Nothing to do here (password is already hashed)
    }

    /**
     * Get user ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get user name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get user email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Helper method to get user data for JWT payload.
     *
     * @return array{user_id: string, name: string, email: string}
     */
    public function getJWTPayload(): array
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
