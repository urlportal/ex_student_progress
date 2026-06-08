<?php

namespace App\Presentation\HTTP\Course;

use App\Application\DTO\Response\CourseResponseDTO;
use App\Application\Service\CourseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListCoursesAction
{
    public function __construct(
        private CourseService $courseService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/courses', name: 'api_v1_courses_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $courses = $this->courseService->findAll();
        $dtos = array_map(
            static fn ($course) => CourseResponseDTO::fromEntity($course),
            $courses,
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
