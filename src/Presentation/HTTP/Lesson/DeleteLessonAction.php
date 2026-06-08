<?php

namespace App\Presentation\HTTP\Lesson;

use App\Application\Service\LessonService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class DeleteLessonAction
{
    public function __construct(
        private LessonService $lessonService,
    ) {
    }

    #[Route('/api/v1/lessons/{id}', name: 'api_v1_lessons_delete', methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $this->lessonService->delete($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
