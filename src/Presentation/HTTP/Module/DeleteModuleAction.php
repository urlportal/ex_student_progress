<?php

namespace App\Presentation\HTTP\Module;

use App\Application\Service\ModuleService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class DeleteModuleAction
{
    public function __construct(
        private ModuleService $moduleService,
    ) {
    }

    #[Route('/api/v1/modules/{id}', name: 'api_v1_modules_delete', methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $this->moduleService->delete($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
