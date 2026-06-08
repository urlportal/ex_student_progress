<?php

namespace App\Presentation\HTTP\User;

use App\Application\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListUsersAction
{
    public function __construct(
        private UserService $userService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/users', name: 'api_v1_users_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $dtos = $this->userService->findAll();

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
