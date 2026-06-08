<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateLessonRequestDTO;
use App\Application\DTO\Request\UpdateLessonRequestDTO;
use App\Application\Service\CourseService;
use App\Application\Service\LessonService;
use App\Application\Service\ModuleService;
use App\Domain\Entity\Course;
use App\Domain\Entity\Lesson;
use App\Domain\Entity\Module;
use App\Domain\Entity\Task;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\InvalidRelationException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\LessonRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LessonServiceTest extends TestCase
{
    private LessonRepositoryInterface&MockObject $lessonRepository;
    private CourseService&MockObject $courseService;
    private ModuleService&MockObject $moduleService;
    private LessonService $service;

    protected function setUp(): void
    {
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->courseService = $this->createMock(CourseService::class);
        $this->moduleService = $this->createMock(ModuleService::class);

        $this->service = new LessonService(
            $this->lessonRepository,
            $this->courseService,
            $this->moduleService,
        );
    }

    private function makeCourse(int $id, string $title = 'Course'): Course
    {
        $course = new Course();
        $course->setTitle($title);
        (new \ReflectionProperty(Course::class, 'id'))->setValue($course, $id);

        return $course;
    }

    private function makeModule(int $id, Course $course): Module
    {
        $module = new Module();
        $module->setTitle('Module');
        $module->setCourse($course);
        (new \ReflectionProperty(Module::class, 'id'))->setValue($module, $id);

        return $module;
    }

    private function makeLesson(int $id, Course $course, string $title = 'Lesson 1'): Lesson
    {
        $lesson = new Lesson();
        $lesson->setTitle($title);
        $lesson->setCourse($course);
        (new \ReflectionProperty(Lesson::class, 'id'))->setValue($lesson, $id);

        return $lesson;
    }

    // --- find() ---

    public function testFindReturnsLesson(): void
    {
        $course = $this->makeCourse(1);
        $lesson = $this->makeLesson(1, $course);
        $this->lessonRepository->method('findById')->with(1)->willReturn($lesson);

        $result = $this->service->find(1);

        self::assertSame($lesson, $result);
    }

    public function testFindThrowsNotFoundExceptionWhenLessonDoesNotExist(): void
    {
        $this->lessonRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->find(99);
    }

    // --- findAll() ---

    public function testFindAllReturnsLessonList(): void
    {
        $course = $this->makeCourse(1);
        $lesson = $this->makeLesson(1, $course);
        $this->lessonRepository->method('findAll')->willReturn([$lesson]);

        $result = $this->service->findAll();

        self::assertSame([$lesson], $result);
    }

    // --- create() ---

    public function testCreateSavesLessonWithCourseOnlyWhenModuleIdIsNull(): void
    {
        $course = $this->makeCourse(1);
        $this->courseService->method('find')->with(1)->willReturn($course);
        $this->lessonRepository->expects($this->once())->method('save');

        $dto = new CreateLessonRequestDTO(title: 'Lesson 1', courseId: 1, moduleId: null);
        $result = $this->service->create($dto);

        self::assertSame('Lesson 1', $result->getTitle());
        self::assertSame($course, $result->getCourse());
        self::assertNull($result->getModule());
    }

    public function testCreateSavesLessonWithModuleWhenModuleIdIsValidAndSameCourse(): void
    {
        $course = $this->makeCourse(1);
        $module = $this->makeModule(10, $course);

        $this->courseService->method('find')->with(1)->willReturn($course);
        $this->moduleService->method('find')->with(10)->willReturn($module);
        $this->lessonRepository->expects($this->once())->method('save');

        $dto = new CreateLessonRequestDTO(title: 'Lesson 1', courseId: 1, moduleId: 10);
        $result = $this->service->create($dto);

        self::assertSame($module, $result->getModule());
    }

    public function testCreateThrowsNotFoundExceptionWhenCourseNotFound(): void
    {
        $this->courseService->method('find')->with(99)->willThrowException(new NotFoundException());

        $this->expectException(NotFoundException::class);
        $this->service->create(new CreateLessonRequestDTO(title: 'Lesson 1', courseId: 99));
    }

    public function testCreateThrowsNotFoundExceptionWhenModuleNotFound(): void
    {
        $course = $this->makeCourse(1);
        $this->courseService->method('find')->with(1)->willReturn($course);
        $this->moduleService->method('find')->with(99)->willThrowException(new NotFoundException());

        $this->expectException(NotFoundException::class);
        $this->service->create(new CreateLessonRequestDTO(title: 'Lesson 1', courseId: 1, moduleId: 99));
    }

    public function testCreateThrowsInvalidRelationExceptionWhenModuleBelongsToAnotherCourse(): void
    {
        $course1 = $this->makeCourse(1, 'Course 1');
        $course2 = $this->makeCourse(2, 'Course 2');
        $module = $this->makeModule(10, $course2);

        $this->courseService->method('find')->with(1)->willReturn($course1);
        $this->moduleService->method('find')->with(10)->willReturn($module);

        $this->expectException(InvalidRelationException::class);
        $this->service->create(new CreateLessonRequestDTO(title: 'Lesson 1', courseId: 1, moduleId: 10));
    }

    // --- update() ---

    public function testUpdateSavesLessonWithNewFields(): void
    {
        $course = $this->makeCourse(1);
        $lesson = $this->makeLesson(1, $course, 'Old Title');
        $this->lessonRepository->method('findById')->with(1)->willReturn($lesson);
        $this->lessonRepository->expects($this->once())->method('save');

        $result = $this->service->update(1, new UpdateLessonRequestDTO(title: 'New Title', sort: 5));

        self::assertSame('New Title', $result->getTitle());
        self::assertSame(5, $result->getSort());
    }

    public function testUpdateThrowsInvalidRelationExceptionWhenModuleIdBelongsToAnotherCourse(): void
    {
        $course1 = $this->makeCourse(1, 'Course 1');
        $course2 = $this->makeCourse(2, 'Course 2');
        $lesson = $this->makeLesson(1, $course1);
        $module = $this->makeModule(10, $course2);

        $this->lessonRepository->method('findById')->with(1)->willReturn($lesson);
        $this->moduleService->method('find')->with(10)->willReturn($module);

        $this->expectException(InvalidRelationException::class);
        $this->service->update(1, new UpdateLessonRequestDTO(moduleId: 10));
    }

    public function testUpdateThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->lessonRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->update(99, new UpdateLessonRequestDTO(title: 'Title'));
    }

    // --- delete() ---

    public function testDeleteCallsRepositoryWhenLessonHasNoTasks(): void
    {
        $course = $this->makeCourse(1);
        $lesson = $this->makeLesson(1, $course);
        $this->lessonRepository->method('findById')->with(1)->willReturn($lesson);
        $this->lessonRepository->expects($this->once())->method('delete')->with($lesson);

        $this->service->delete(1);
    }

    public function testDeleteThrowsHasDependenciesExceptionWhenLessonHasTasks(): void
    {
        $task = $this->createMock(Task::class);
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTasks')->willReturn(new ArrayCollection([$task]));
        $lesson->method('getId')->willReturn(1);

        $this->lessonRepository->method('findById')->with(1)->willReturn($lesson);

        $this->expectException(HasDependenciesException::class);
        $this->service->delete(1);
    }

    public function testDeleteThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->lessonRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->delete(99);
    }
}
