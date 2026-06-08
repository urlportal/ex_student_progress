<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateCourseRequestDTO;
use App\Application\DTO\Request\UpdateCourseRequestDTO;
use App\Application\Service\CourseService;
use App\Domain\Entity\Course;
use App\Domain\Entity\Module;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\CourseRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class CourseServiceTest extends TestCase
{
    private CourseRepositoryInterface&MockObject $courseRepository;
    private CacheInterface&MockObject $cache;
    private MessageBusInterface&MockObject $bus;
    private LoggerInterface&MockObject $logger;
    private CourseService $service;

    protected function setUp(): void
    {
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new CourseService(
            $this->courseRepository,
            $this->cache,
            $this->bus,
            $this->logger,
            'test_list_courses',
        );
    }

    private function makeCourse(int $id, string $title = 'Course A'): Course
    {
        $course = new Course();
        $course->setTitle($title);
        (new \ReflectionProperty(Course::class, 'id'))->setValue($course, $id);

        return $course;
    }

    // --- find() ---

    public function testFindReturnsCourse(): void
    {
        $course = $this->makeCourse(1);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);

        $result = $this->service->find(1);

        self::assertSame($course, $result);
    }

    public function testFindThrowsNotFoundExceptionWhenCourseDoesNotExist(): void
    {
        $this->courseRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->find(99);
    }

    // --- findAll() ---

    public function testFindAllReturnsCachedData(): void
    {
        $course = $this->makeCourse(1);
        $this->cache->method('get')->willReturn([$course]);

        $this->courseRepository->expects($this->never())->method('findAll');

        $result = $this->service->findAll();

        self::assertSame([$course], $result);
    }

    public function testFindAllFallsBackToRepositoryOnCacheMiss(): void
    {
        $course = $this->makeCourse(1);
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): array {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->courseRepository->expects($this->once())->method('findAll')->willReturn([$course]);

        $result = $this->service->findAll();

        self::assertSame([$course], $result);
    }

    public function testFindAllFallsBackToRepositoryOnCacheException(): void
    {
        $course = $this->makeCourse(1);
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->courseRepository->expects($this->once())->method('findAll')->willReturn([$course]);

        $result = $this->service->findAll();

        self::assertSame([$course], $result);
    }

    // --- create() ---

    public function testCreateDispatchesCacheInvalidation(): void
    {
        $this->courseRepository->method('findByTitle')->willReturn(null);
        $this->courseRepository->expects($this->once())->method('save');

        $envelope = new Envelope(new \stdClass());
        $this->bus->expects($this->exactly(2))->method('dispatch')->willReturn($envelope);

        $dto = new CreateCourseRequestDTO(title: 'New Course', description: null);
        $result = $this->service->create($dto);

        self::assertSame('New Course', $result->getTitle());
    }

    public function testCreateThrowsAlreadyExistsExceptionWhenTitleTaken(): void
    {
        $existing = $this->makeCourse(1, 'New Course');
        $this->courseRepository->method('findByTitle')->willReturn($existing);

        $this->expectException(AlreadyExistsException::class);
        $this->service->create(new CreateCourseRequestDTO(title: 'New Course'));
    }

    public function testCreateSucceedsEvenWhenDispatchThrowsException(): void
    {
        $this->courseRepository->method('findByTitle')->willReturn(null);
        $this->courseRepository->expects($this->once())->method('save');
        $this->bus->method('dispatch')->willThrowException(new \RuntimeException('Bus error'));

        $dto = new CreateCourseRequestDTO(title: 'New Course');
        $result = $this->service->create($dto);

        self::assertSame('New Course', $result->getTitle());
    }

    // --- update() ---

    public function testUpdateSavesAndReturnsCourseWhenTitleIsUnique(): void
    {
        $course = $this->makeCourse(1, 'Old Title');
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->courseRepository->method('findByTitle')->with('New Title')->willReturn(null);
        $this->courseRepository->expects($this->once())->method('save');
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $dto = new UpdateCourseRequestDTO(title: 'New Title');
        $result = $this->service->update(1, $dto);

        self::assertSame('New Title', $result->getTitle());
    }

    public function testUpdateThrowsAlreadyExistsExceptionWhenNewTitleTaken(): void
    {
        $course = $this->makeCourse(1, 'Old Title');
        $other = $this->makeCourse(2, 'Taken Title');
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->courseRepository->method('findByTitle')->with('Taken Title')->willReturn($other);

        $this->expectException(AlreadyExistsException::class);
        $this->service->update(1, new UpdateCourseRequestDTO(title: 'Taken Title'));
    }

    public function testUpdateThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->courseRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->update(99, new UpdateCourseRequestDTO(title: 'Title'));
    }

    public function testUpdateSavesEvenWhenAllNullableFieldsAreNull(): void
    {
        $course = $this->makeCourse(1, 'Title');
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->courseRepository->expects($this->once())->method('save');
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $dto = new UpdateCourseRequestDTO(title: null, description: null, isActive: null);
        $result = $this->service->update(1, $dto);

        self::assertSame($course, $result);
    }

    // --- delete() ---

    public function testDeleteCallsRepositoryAndInvalidatesCacheWhenNoDependencies(): void
    {
        $course = $this->makeCourse(1);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->courseRepository->expects($this->once())->method('delete')->with($course);
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->service->delete(1);
    }

    public function testDeleteThrowsHasDependenciesExceptionWhenCourseHasModules(): void
    {
        $course = $this->makeCourse(1);
        $module = new Module();
        $module->setTitle('M');
        $course->addModule($module);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);

        $this->expectException(HasDependenciesException::class);
        $this->service->delete(1);
    }

    public function testDeleteThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->courseRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->delete(99);
    }
}
