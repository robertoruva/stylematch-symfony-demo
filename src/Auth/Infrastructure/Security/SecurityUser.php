<?php

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Adapter: Wraps Domain User to comply with Symfony Security contracts
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private User $domainUser;

    public function __construct(User $domainUser)
    {
        $this->domainUser = $domainUser;
    }

    /**
     * Get the wrapped Domain User
     */
    public function getDomainUser(): User
    {
        return $this->domainUser;
    }

    /**
     * Symfony Security UserInterface implementation
     */
    public function getUserIdentifier(): string
    {
        return $this->domainUser->getEmail()->value();
    }

    /**
     * Symfony Security PasswordAuthenticatedUserInterface implementation
     */
    public function getPassword(): string
    {
        return $this->domainUser->getPassword()->value();
    }

    /**
     * Symfony Security UserInterface implementation
     */
    public function getRoles(): array
    {
        // For now, all users have ROLE_USER
        // You can extend this later with User roles from Domain
        return ['ROLE_USER'];
    }

    /**
     * Symfony Security UserInterface implementation
     */
    public function eraseCredentials(): void
    {
        // Nothing to do here (password is already hashed in Domain)
    }

    /**
     * Helper method to get user data for JWT payload
     */
    public function getJWTPayload(): array
    {
        return [
            'user_id' => $this->domainUser->getId()->value(),
            'name' => $this->domainUser->getName(),
            'email' => $this->domainUser->getEmail()->value(),
        ];
    }
}
