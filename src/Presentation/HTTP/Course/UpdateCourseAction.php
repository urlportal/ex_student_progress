<?php

namespace App\Presentation\HTTP\Course;

use App\Application\DTO\Request\UpdateCourseRequestDTO;
use App\Application\DTO\Response\CourseResponseDTO;
use App\Application\Service\CourseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class UpdateCourseAction
{
    public function __construct(
        private CourseService $courseService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/courses/{id}', name: 'api_v1_courses_update', methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload] UpdateCourseRequestDTO $dto): Response
    {
        $course = $this->courseService->update($id, $dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(CourseResponseDTO::fromEntity($course), 'json'),
            Response::HTTP_OK
        );
    }
}
