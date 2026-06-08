<?php

namespace App\Presentation\HTTP\Module;

use App\Application\DTO\Response\ModuleResponseDTO;
use App\Application\Service\ModuleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListModulesAction
{
    public function __construct(
        private ModuleService $moduleService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/modules', name: 'api_v1_modules_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $modules = $this->moduleService->findAll();
        $dtos = array_map(
            static fn ($module) => ModuleResponseDTO::fromEntity($module),
            $modules,
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
