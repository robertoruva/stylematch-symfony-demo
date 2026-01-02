<?php

namespace App\Auth\Application\Command\LoginUser;

use App\Auth\Application\DTO\AuthResponseDTO;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Domain\Exception\UserNotFoundException;
use App\Auth\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\Email;

class LoginUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenGeneratorInterface $tokenGenerator
    ) {
    }

    public function __invoke(LoginUserCommand $command): AuthResponseDTO
    {
        return $this->handle($command);
    }

    /**
     * @throws UserNotFoundException
     * @throws InvalidCredentialsException
     */
    public function handle(LoginUserCommand $command): AuthResponseDTO
    {
        $user = $this->userRepository->findByEmail(new Email($command->email));

        if (!$user) {
            throw new UserNotFoundException($command->email);
        }
        if (!$user->getPassword()->matches($command->password)) {
            throw InvalidCredentialsException::create();
        }

        $token = $this->tokenGenerator->generate($user);

        return new AuthResponseDTO(
            $user->getId()->value(),
            $user->getName(),
            $user->getEmail()->value(),
            $token
        );
    }
}
