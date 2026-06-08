<?php

namespace App\Presentation\HTTP;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class HealthcheckAction
{
    #[Route(path: '/healthcheck', name: 'healthcheck', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }
}
