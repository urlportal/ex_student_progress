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
final readonly class ListSkillsAction
{
    public function __construct(
        private SkillService $skillService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/skills', name: 'api_v1_skills_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $skills = $this->skillService->findAll();
        $dtos = array_map(static fn ($skill) => SkillResponseDTO::fromEntity($skill), $skills);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
