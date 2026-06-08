<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\StudentCourseScoreResponseDTO;
use App\Application\Service\AggStudentCourseService;
use App\Domain\Entity\AggStudentCourse;
use App\Domain\Entity\Course;
use App\Domain\Entity\User;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\AggStudentCourseRepositoryInterface;
use App\Domain\Repository\CourseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class AggStudentCourseServiceTest extends TestCase
{
    private AggStudentCourseRepositoryInterface&MockObject $aggRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private CourseRepositoryInterface&MockObject $courseRepository;
    private CacheInterface&MockObject $cache;
    private AggStudentCourseService $service;

    protected function setUp(): void
    {
        $this->aggRepository = $this->createMock(AggStudentCourseRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->service = new AggStudentCourseService(
            $this->aggRepository,
            $this->userRepository,
            $this->courseRepository,
            $this->cache,
        );
    }

    public function testGetReturnsEmptyDtoWhenNoAggregationExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentCourseScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->courseRepository->method('findById')->willReturn($this->createMock(Course::class));
        $this->aggRepository->method('findByStudentAndCourse')->willReturn(null);

        $result = $this->service->getByStudentAndCourse('uuid', 10);

        self::assertInstanceOf(StudentCourseScoreResponseDTO::class, $result);
        self::assertSame(10, $result->courseId);
        self::assertSame(0.0, $result->totalScore);
    }

    public function testGetReturnsDtoFromEntity(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentCourseScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(10);
        $this->courseRepository->method('findById')->willReturn($course);

        $entity = $this->createMock(AggStudentCourse::class);
        $entity->method('getCourse')->willReturn($course);
        $entity->method('getTotalScore')->willReturn(42.0);
        $this->aggRepository->method('findByStudentAndCourse')->willReturn($entity);

        $result = $this->service->getByStudentAndCourse('uuid', 10);

        self::assertInstanceOf(StudentCourseScoreResponseDTO::class, $result);
        self::assertSame(10, $result->courseId);
        self::assertSame(42.0, $result->totalScore);
    }

    public function testGetThrowsNotFoundWhenStudentNotExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentCourseScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndCourse('uuid', 10);
    }

    public function testGetThrowsNotFoundWhenCourseNotExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentCourseScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->courseRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndCourse('uuid', 10);
    }

    public function testGetFallsBackToRepositoryWhenCacheThrows(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(10);
        $this->courseRepository->method('findById')->willReturn($course);

        $entity = $this->createMock(AggStudentCourse::class);
        $entity->method('getCourse')->willReturn($course);
        $entity->method('getTotalScore')->willReturn(42.0);
        $this->aggRepository->method('findByStudentAndCourse')->willReturn($entity);

        $result = $this->service->getByStudentAndCourse('uuid', 10);

        self::assertInstanceOf(StudentCourseScoreResponseDTO::class, $result);
        self::assertSame(10, $result->courseId);
        self::assertSame(42.0, $result->totalScore);
    }

    public function testFallbackThrowsNotFoundWhenStudentNotExists(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndCourse('uuid', 10);
    }

    public function testFallbackThrowsNotFoundWhenCourseNotExists(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->courseRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndCourse('uuid', 10);
    }
}
