<?php

namespace App\Presentation\HTTP\Course;

use App\Application\Service\CourseService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class DeleteCourseAction
{
    public function __construct(
        private CourseService $courseService,
    ) {
    }

    #[Route('/api/v1/courses/{id}', name: 'api_v1_courses_delete', methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $this->courseService->delete($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
