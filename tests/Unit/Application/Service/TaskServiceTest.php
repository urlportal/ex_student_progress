<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateTaskRequestDTO;
use App\Application\DTO\Request\UpdateTaskRequestDTO;
use App\Application\Service\LessonService;
use App\Application\Service\SkillService;
use App\Application\Service\TaskService;
use App\Domain\Entity\Lesson;
use App\Domain\Entity\Skill;
use App\Domain\Entity\Task;
use App\Domain\Entity\TaskSkill;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\InvalidRelationException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\TaskRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TaskServiceTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $taskRepository;
    private LessonService&MockObject $lessonService;
    private SkillService&MockObject $skillService;
    private TaskService $service;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->lessonService = $this->createMock(LessonService::class);
        $this->skillService = $this->createMock(SkillService::class);

        $this->service = new TaskService(
            $this->taskRepository,
            $this->lessonService,
            $this->skillService,
        );
    }

    private function makeLesson(int $id): Lesson
    {
        $course = $this->createMock(\App\Domain\Entity\Course::class);
        $lesson = new Lesson();
        $lesson->setTitle('Lesson');
        $lesson->setCourse($course);
        (new \ReflectionProperty(Lesson::class, 'id'))->setValue($lesson, $id);

        return $lesson;
    }

    private function makeSkill(int $id): Skill
    {
        $skill = new Skill();
        $skill->setTitle('Skill '.$id);
        (new \ReflectionProperty(Skill::class, 'id'))->setValue($skill, $id);

        return $skill;
    }

    private function makeTask(int $id, Lesson $lesson): Task
    {
        $task = new Task();
        $task->setTitle('Task');
        $task->setLesson($lesson);
        (new \ReflectionProperty(Task::class, 'id'))->setValue($task, $id);

        return $task;
    }

    // --- find() ---

    public function testFindReturnsTask(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $this->taskRepository->method('findById')->with(1)->willReturn($task);

        $result = $this->service->find(1);

        self::assertSame($task, $result);
    }

    public function testFindThrowsNotFoundExceptionWhenMissing(): void
    {
        $this->taskRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->find(99);
    }

    // --- findAll() ---

    public function testFindAllReturnsTasksArray(): void
    {
        $lesson = $this->makeLesson(1);
        $task1 = $this->makeTask(1, $lesson);
        $task2 = $this->makeTask(2, $lesson);
        $this->taskRepository->method('findAll')->willReturn([$task1, $task2]);

        $result = $this->service->findAll();

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(Task::class, $result);
    }

    // --- create() ---

    public function testCreateSavesTaskWithCorrectValues(): void
    {
        $lesson = $this->makeLesson(5);
        $this->lessonService->method('find')->with(5)->willReturn($lesson);
        $this->taskRepository->expects($this->once())->method('save');

        $dto = new CreateTaskRequestDTO(
            title: 'T',
            lessonId: 5,
            description: 'D',
            scoreMin: 3,
            scoreMax: 7,
            sort: 5,
        );

        $result = $this->service->create($dto);

        self::assertSame('T', $result->getTitle());
        self::assertSame('D', $result->getDescription());
        self::assertSame(3, $result->getScoreMin());
        self::assertSame(7, $result->getScoreMax());
        self::assertSame(5, $result->getSort());
        self::assertSame($lesson, $result->getLesson());
    }

    public function testCreateAppliesDefaultScores(): void
    {
        $lesson = $this->makeLesson(1);
        $this->lessonService->method('find')->willReturn($lesson);
        $this->taskRepository->method('save');

        $dto = new CreateTaskRequestDTO(title: 'T', lessonId: 1);

        $result = $this->service->create($dto);

        self::assertSame(TaskService::MIN_TASK_SCORE, $result->getScoreMin());
        self::assertSame(TaskService::MAX_TASK_SCORE, $result->getScoreMax());
    }

    public function testCreateThrowsNotFoundExceptionWhenLessonMissing(): void
    {
        $this->lessonService->method('find')->willThrowException(new NotFoundException());

        $this->expectException(NotFoundException::class);
        $this->service->create(new CreateTaskRequestDTO(title: 'T', lessonId: 999));
    }

    public function testCreateThrowsInvalidRelationExceptionWhenScoreMinGtMax(): void
    {
        $lesson = $this->makeLesson(1);
        $this->lessonService->method('find')->willReturn($lesson);

        $this->expectException(InvalidRelationException::class);
        $this->service->create(new CreateTaskRequestDTO(title: 'T', lessonId: 1, scoreMin: 8, scoreMax: 3));
    }

    // --- update() ---

    public function testUpdateDoesNotOverwriteNullFields(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $task->setTitle('Original');
        $task->setDescription('Desc');
        $this->taskRepository->method('findById')->with(1)->willReturn($task);
        $this->taskRepository->method('save');

        $dto = new UpdateTaskRequestDTO(title: 'New');
        $result = $this->service->update(1, $dto);

        self::assertSame('New', $result->getTitle());
        self::assertSame('Desc', $result->getDescription());
    }

    public function testUpdateThrowsInvalidRelationExceptionWhenScoresInvalid(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $task->setScoreMin(3);
        $task->setScoreMax(7);
        $this->taskRepository->method('findById')->with(1)->willReturn($task);

        // scoreMin=9, scoreMax=null → effectiveMax uses existing 7 → 9 > 7
        $this->expectException(InvalidRelationException::class);
        $this->service->update(1, new UpdateTaskRequestDTO(scoreMin: 9));
    }

    // --- delete() ---

    public function testDeleteCallsRepositoryDelete(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $this->taskRepository->method('findById')->with(1)->willReturn($task);
        $this->taskRepository->expects($this->once())->method('delete')->with($task);

        $this->service->delete(1);
    }

    // --- addSkill() ---

    public function testAddSkillReturnsTaskSkill(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $skill = $this->makeSkill(42);

        $this->taskRepository->method('findById')->willReturn($task);
        $this->skillService->method('find')->with(42)->willReturn($skill);
        $this->taskRepository->method('save');

        $result = $this->service->addSkill(1, 42, 1.5);

        self::assertInstanceOf(TaskSkill::class, $result);
        self::assertSame(42, $result->getSkill()->getId());
        self::assertSame(1.5, $result->getWeight());
    }

    public function testAddSkillThrowsAlreadyExistsExceptionWhenDuplicate(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $skill = $this->makeSkill(42);

        // Pre-populate task with this skill
        $task->addSkill($skill, 1.0);

        $this->taskRepository->method('findById')->willReturn($task);

        $this->expectException(AlreadyExistsException::class);
        $this->service->addSkill(1, 42, 2.0);
    }

    public function testAddSkillThrowsNotFoundExceptionWhenTaskMissing(): void
    {
        $this->taskRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->addSkill(999, 42, 1.0);
    }

    public function testAddSkillThrowsNotFoundExceptionWhenSkillMissing(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $this->taskRepository->method('findById')->willReturn($task);
        $this->skillService->method('find')->willThrowException(new NotFoundException());

        $this->expectException(NotFoundException::class);
        $this->service->addSkill(1, 999, 1.0);
    }

    // --- removeSkill() ---

    public function testRemoveSkillCallsDeleteOnRepository(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $skill = $this->makeSkill(5);
        $taskSkill = new TaskSkill($task, $skill, 1.0);

        $this->taskRepository->method('findById')->with(1)->willReturn($task);
        $this->taskRepository->method('findTaskSkill')->with(1, 5)->willReturn($taskSkill);
        $this->taskRepository->expects($this->once())->method('deleteTaskSkill')->with($taskSkill);

        $this->service->removeSkill(1, 5);
    }

    public function testRemoveSkillThrowsNotFoundExceptionWhenLinkMissing(): void
    {
        $lesson = $this->makeLesson(1);
        $task = $this->makeTask(1, $lesson);
        $this->taskRepository->method('findById')->with(1)->willReturn($task);
        $this->taskRepository->method('findTaskSkill')->with(1, 5)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->removeSkill(1, 5);
    }
}
