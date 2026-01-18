<?php

namespace App\Auth\Application\Query\CheckEmailAvailability;

use App\Auth\Application\DTO\EmailAvailabilityDTO;
use App\Auth\Domain\Exception\InvalidEmailException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\ValueObject\Email;

final readonly class CheckEmailAvailabilityHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(CheckEmailAvailabilityQuery $query): EmailAvailabilityDTO
    {
        try {
            $email = new Email($query->email);
        } catch (InvalidEmailException) {
            return new EmailAvailabilityDTO(
                email: $query->email,
                available: false,
                reason: 'Invalid email format'
            );
        }

        $exists = $this->userRepository->existsByEmail($email);

        return new EmailAvailabilityDTO(
            email: $query->email,
            available: !$exists,
            reason: $exists ? 'Email already registered' : null
        );
    }
}
