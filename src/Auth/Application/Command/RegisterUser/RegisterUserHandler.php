<?php

namespace App\Auth\Application\Command\RegisterUser;

use App\Auth\Application\DTO\AuthResponseDTO;
use App\Auth\Application\Message\MessageBusInterface;
use App\Auth\Application\Message\UserRegisteredMessage;
use App\Auth\Domain\Entity\User;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\UserId;

class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(RegisterUserCommand $command): AuthResponseDTO
    {
        return $this->handle($command);
    }

    public function handle(RegisterUserCommand $command): AuthResponseDTO
    {
        $email = new Email($command->email);

        $existingUser = $this->userRepository->findByEmail($email);

        if ($existingUser) {
            throw new UserAlreadyExistsException($email->value());
        }

        $user = new User(
            UserId::random(),
            $command->name,
            new Email($command->email),
            PasswordHash::fromPlainText($command->password)
        );

        $this->userRepository->save($user);

        $token = $this->tokenGenerator->generate($user);

        $this->messageBus->publish(
            message: new UserRegisteredMessage(
                userId: $user->getId()->value(),
                name: $user->getName(),
                email: $user->getEmail()->value(),
                occurredAt: (new \DateTimeImmutable())->format('c')
            ),
            queue: 'user_registered'
        );

        return new AuthResponseDTO(
            $user->getId()->value(),
            $user->getName(),
            $user->getEmail()->value(),
            $token
        );
    }
}
