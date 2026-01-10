<?php

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Command\LoginUser\LoginUserCommand;
use App\Auth\Application\Command\LoginUser\LoginUserHandler;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly LoginUserHandler $handler
    ) {
    }

    #[Route('/api/auth/login', name: 'auth_login', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json([
                'message' => 'Missing required fields: email, password',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new LoginUserCommand(
                email: $data['email'],
                password: $data['password']
            );

            $response = ($this->handler)($command);

            return $this->json(
                $response->toArray(),
                Response::HTTP_OK
            );
        } catch (InvalidCredentialsException $e) {
            return $this->json([
                'error' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
