<?php

namespace App\Presentation\HTTP\Module;

use App\Application\DTO\Request\CreateModuleRequestDTO;
use App\Application\DTO\Response\ModuleResponseDTO;
use App\Application\Service\ModuleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class CreateModuleAction
{
    public function __construct(
        private ModuleService $moduleService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/modules', name: 'api_v1_modules_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateModuleRequestDTO $dto): Response
    {
        $module = $this->moduleService->create($dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(ModuleResponseDTO::fromEntity($module), 'json'),
            Response::HTTP_CREATED
        );
    }
}
