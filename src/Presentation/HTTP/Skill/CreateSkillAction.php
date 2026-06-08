<?php

namespace App\Presentation\HTTP\Skill;

use App\Application\DTO\Request\CreateSkillRequestDTO;
use App\Application\DTO\Response\SkillResponseDTO;
use App\Application\Service\SkillService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class CreateSkillAction
{
    public function __construct(
        private SkillService $skillService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/skills', name: 'api_v1_skills_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateSkillRequestDTO $dto): Response
    {
        $skill = $this->skillService->create($dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(SkillResponseDTO::fromEntity($skill), 'json'),
            Response::HTTP_CREATED
        );
    }
}
