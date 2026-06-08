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
final readonly class GetCourseAction
{
    public function __construct(
        private CourseService $courseService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/courses/{id}', name: 'api_v1_courses_get', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $course = $this->courseService->find($id);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(CourseResponseDTO::fromEntity($course), 'json'),
            Response::HTTP_OK
        );
    }
}
