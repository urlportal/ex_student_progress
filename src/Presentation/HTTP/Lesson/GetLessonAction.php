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
final readonly class GetLessonAction
{
    public function __construct(
        private LessonService $lessonService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/lessons/{id}', name: 'api_v1_lessons_get', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $lesson = $this->lessonService->find($id);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(LessonResponseDTO::fromEntity($lesson), 'json'),
            Response::HTTP_OK
        );
    }
}
