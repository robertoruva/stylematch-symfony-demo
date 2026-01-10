<?php

namespace App\Auth\Infrastructure\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MeController extends AbstractController
{
    #[Route('/api/auth/me', name: 'auth_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'message' => 'TODO: Get authenticated user',
            'user' => [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
        ], Response::HTTP_OK);
    }
}
