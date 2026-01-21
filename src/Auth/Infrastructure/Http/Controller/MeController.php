<?php

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Query\GetCurrentUser\GetCurrentUserHandler;
use App\Auth\Application\Query\GetCurrentUser\GetCurrentUserQuery;
use App\Auth\Domain\Exception\UserNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MeController extends AbstractController
{
    public function __construct(
        private readonly GetCurrentUserHandler $handler
    ) {
    }

    #[Route('/api/auth/me', name: 'auth_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(): JsonResponse
    {
        /** @var \App\Auth\Infrastructure\Security\SecurityUser $securityUser */
        $securityUser = $this->getUser();

        try {
            $query = new GetCurrentUserQuery($securityUser->getUserIdentifier());
            $userDTO = ($this->handler)($query);

            return $this->json(
                $userDTO->toArray(),
                Response::HTTP_OK
            );
        } catch (UserNotFoundException) {
            return $this->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
