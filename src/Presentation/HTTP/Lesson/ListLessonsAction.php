<?php

namespace App\Presentation\HTTP\Lesson;

use App\Application\DTO\Response\LessonResponseDTO;
use App\Application\Service\LessonService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListLessonsAction
{
    public function __construct(
        private LessonService $lessonService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/lessons', name: 'api_v1_lessons_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $lessons = $this->lessonService->findAll();
        $dtos = array_map(
            static fn ($lesson) => LessonResponseDTO::fromEntity($lesson),
            $lessons,
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
