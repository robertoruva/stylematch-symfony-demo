<?php

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Auth\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Auth\Domain\Exception\InvalidTokenException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RefreshTokenController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenHandler $handler
    ) {
    }

    #[Route('/api/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $refreshToken = $data['refresh_token'] ?? $data['refreshToken'] ?? null;

        if (!$refreshToken) {
            return $this->json([
                'message' => 'Missing required field: refreshToken',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new RefreshTokenCommand($refreshToken);
            $result = ($this->handler)($command);

            return $this->json($result->toArray(), Response::HTTP_OK);
        } catch (InvalidTokenException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
