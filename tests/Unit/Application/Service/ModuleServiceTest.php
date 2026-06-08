<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateModuleRequestDTO;
use App\Application\DTO\Request\UpdateModuleRequestDTO;
use App\Application\Service\CourseService;
use App\Application\Service\ModuleService;
use App\Domain\Entity\Course;
use App\Domain\Entity\Lesson;
use App\Domain\Entity\Module;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\ModuleRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ModuleServiceTest extends TestCase
{
    private ModuleRepositoryInterface&MockObject $moduleRepository;
    private CourseService&MockObject $courseService;
    private ModuleService $service;

    protected function setUp(): void
    {
        $this->moduleRepository = $this->createMock(ModuleRepositoryInterface::class);
        $this->courseService = $this->createMock(CourseService::class);
        $this->service = new ModuleService($this->moduleRepository, $this->courseService);
    }

    private function makeCourse(int $id): Course
    {
        $course = new Course();
        $course->setTitle('Course');
        (new \ReflectionProperty(Course::class, 'id'))->setValue($course, $id);

        return $course;
    }

    private function makeModule(int $id, Course $course, string $title = 'Module 1'): Module
    {
        $module = new Module();
        $module->setTitle($title);
        $module->setCourse($course);
        (new \ReflectionProperty(Module::class, 'id'))->setValue($module, $id);

        return $module;
    }

    // --- find() ---

    public function testFindReturnsModule(): void
    {
        $course = $this->makeCourse(1);
        $module = $this->makeModule(1, $course);
        $this->moduleRepository->method('findById')->with(1)->willReturn($module);

        $result = $this->service->find(1);

        self::assertSame($module, $result);
    }

    public function testFindThrowsNotFoundExceptionWhenModuleDoesNotExist(): void
    {
        $this->moduleRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->find(99);
    }

    // --- findAll() ---

    public function testFindAllReturnsModuleList(): void
    {
        $course = $this->makeCourse(1);
        $module = $this->makeModule(1, $course);
        $this->moduleRepository->method('findAll')->willReturn([$module]);

        $result = $this->service->findAll();

        self::assertSame([$module], $result);
    }

    // --- create() ---

    public function testCreateSavesModuleLinkedToCourse(): void
    {
        $course = $this->makeCourse(1);
        $this->courseService->method('find')->with(1)->willReturn($course);
        $this->moduleRepository->expects($this->once())->method('save');

        $dto = new CreateModuleRequestDTO(title: 'Module 1', courseId: 1, description: null);
        $result = $this->service->create($dto);

        self::assertSame('Module 1', $result->getTitle());
        self::assertSame($course, $result->getCourse());
    }

    public function testCreateThrowsNotFoundExceptionWhenCourseNotFound(): void
    {
        $this->courseService->method('find')->with(99)->willThrowException(new NotFoundException());

        $this->expectException(NotFoundException::class);
        $this->service->create(new CreateModuleRequestDTO(title: 'Module 1', courseId: 99));
    }

    // --- update() ---

    public function testUpdateSavesModuleWithNewFields(): void
    {
        $course = $this->makeCourse(1);
        $module = $this->makeModule(1, $course, 'Old Title');
        $this->moduleRepository->method('findById')->with(1)->willReturn($module);
        $this->moduleRepository->expects($this->once())->method('save');

        $result = $this->service->update(1, new UpdateModuleRequestDTO(title: 'New Title', description: 'Desc'));

        self::assertSame('New Title', $result->getTitle());
        self::assertSame('Desc', $result->getDescription());
    }

    public function testUpdateThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->moduleRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->update(99, new UpdateModuleRequestDTO(title: 'Title'));
    }

    // --- delete() ---

    public function testDeleteCallsRepositoryWhenModuleHasNoLessons(): void
    {
        $course = $this->makeCourse(1);
        $module = $this->makeModule(1, $course);
        $this->moduleRepository->method('findById')->with(1)->willReturn($module);
        $this->moduleRepository->expects($this->once())->method('delete')->with($module);

        $this->service->delete(1);
    }

    public function testDeleteThrowsHasDependenciesExceptionWhenModuleHasLessons(): void
    {
        $course = $this->makeCourse(1);
        $module = $this->makeModule(1, $course);

        $lesson = new Lesson();
        $lesson->setTitle('L');
        $lesson->setCourse($course);
        $module->addLesson($lesson);

        $this->moduleRepository->method('findById')->with(1)->willReturn($module);

        $this->expectException(HasDependenciesException::class);
        $this->service->delete(1);
    }

    public function testDeleteThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->moduleRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->delete(99);
    }
}
