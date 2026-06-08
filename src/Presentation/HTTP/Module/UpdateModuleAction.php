<?php

namespace App\Presentation\HTTP\Module;

use App\Application\DTO\Request\UpdateModuleRequestDTO;
use App\Application\DTO\Response\ModuleResponseDTO;
use App\Application\Service\ModuleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class UpdateModuleAction
{
    public function __construct(
        private ModuleService $moduleService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/modules/{id}', name: 'api_v1_modules_update', methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload] UpdateModuleRequestDTO $dto): Response
    {
        $module = $this->moduleService->update($id, $dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(ModuleResponseDTO::fromEntity($module), 'json'),
            Response::HTTP_OK
        );
    }
}
