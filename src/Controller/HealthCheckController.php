<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthCheckController extends AbstractController
{
    #[Route('/api/health', name: 'health_check', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'framework' => 'Symfony 7.1',
            'architecture' => 'DDD + Hexagonal + CQRS',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339),
        ]);
    }
}
