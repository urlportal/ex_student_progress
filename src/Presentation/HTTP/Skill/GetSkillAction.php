<?php

namespace App\Presentation\HTTP\Skill;

use App\Application\DTO\Response\SkillResponseDTO;
use App\Application\Service\SkillService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class GetSkillAction
{
    public function __construct(
        private SkillService $skillService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/skills/{id}', name: 'api_v1_skills_get', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $skill = $this->skillService->find($id);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(SkillResponseDTO::fromEntity($skill), 'json'),
            Response::HTTP_OK
        );
    }
}
