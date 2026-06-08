<?php

namespace App\Presentation\HTTP\Skill;

use App\Application\Service\SkillService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class DeleteSkillAction
{
    public function __construct(
        private SkillService $skillService,
    ) {
    }

    #[Route('/api/v1/skills/{id}', name: 'api_v1_skills_delete', methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $this->skillService->delete($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
