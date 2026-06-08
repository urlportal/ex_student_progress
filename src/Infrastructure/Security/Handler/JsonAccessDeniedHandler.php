<?php

namespace App\Infrastructure\Security\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class JsonAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): JsonResponse
    {
        return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
    }
}
