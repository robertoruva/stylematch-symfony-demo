<?php

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Command\RegisterUser\RegisterUserCommand;
use App\Auth\Application\Command\RegisterUser\RegisterUserHandler;
use App\Auth\Domain\Exception\UserAlreadyExistsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserHandler $handler
    ) {
    }

    #[Route('/api/auth/register', name: 'auth_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['password'])) {
            return $this->json([
                'error' => 'Missing required fields: name, email, password',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new RegisterUserCommand(
                name: $data['name'],
                email: $data['email'],
                password: $data['password']
            );

            ($this->handler)($command);

            return $this->json([
                'message' => 'User registered successfully',
            ], Response::HTTP_CREATED);
        } catch (UserAlreadyExistsException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Registration failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
