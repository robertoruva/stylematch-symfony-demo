<?php

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Command\LogoutUser\LogoutUserCommand;
use App\Auth\Application\Command\LogoutUser\LogoutUserHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly LogoutUserHandler $handler
    ) {}

    #[Route('/api/auth/logout', name: 'auth_logout', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');

        if(!$token) {
            return $this->json([
                'error' => 'Missing authorization token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = str_replace('Bearer ', '', $token);

        // Validate token is not empty after cleaning
        if (empty($token)) {
            return $this->json([
                'error' => 'Invalid authorization token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        error_log("LOGOUT CONTROLLER: Token recibido: " . substr($token, 0, 32) . "...");
        error_log("LOGOUT CONTROLLER: Token length: " . strlen($token));

        try {
            $command = new LogoutUserCommand(token: $token);

            ($this->handler)($command);

            return $this->json([
                'message' => 'Logout successful'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'logout failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
