<?php

namespace App\Presentation\HTTP\User;

use App\Application\DTO\Request\CreateUserRequestDTO;
use App\Application\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class CreateUserAction
{
    public function __construct(
        private UserService $userService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/users', name: 'api_v1_users_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateUserRequestDTO $dto): Response
    {
        $responseDto = $this->userService->create($dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($responseDto, 'json'),
            Response::HTTP_CREATED
        );
    }
}
