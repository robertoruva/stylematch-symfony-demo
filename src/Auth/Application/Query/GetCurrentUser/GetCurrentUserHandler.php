<?php

namespace App\Auth\Application\Query\GetCurrentUser;

use App\Auth\Application\DTO\UserDTO;
use App\Auth\Domain\Exception\UserNotFoundException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\ValueObject\UserId;

final readonly class GetCurrentUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(GetCurrentUserQuery $query): UserDTO
    {
        $userId = new UserId($query->userId);

        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new UserNotFoundException("User with ID {$query->userId} not found");
        }

        return new UserDTO(
            id: $user->getId()->value(),
            email: $user->getEmail()->value(),
            createdAt: $user->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $user->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
