<?php

namespace App\Presentation\HTTP\Lesson;

use App\Application\DTO\Request\CreateLessonRequestDTO;
use App\Application\DTO\Response\LessonResponseDTO;
use App\Application\Service\LessonService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class CreateLessonAction
{
    public function __construct(
        private LessonService $lessonService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/lessons', name: 'api_v1_lessons_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateLessonRequestDTO $dto): Response
    {
        $lesson = $this->lessonService->create($dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(LessonResponseDTO::fromEntity($lesson), 'json'),
            Response::HTTP_CREATED
        );
    }
}
