<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateTaskExecutionRequestDTO;
use App\Application\Message\RecalculateStudentLessonSkillScores;
use App\Application\Service\TaskExecutionService;
use App\Domain\Entity\Task;
use App\Domain\Entity\TaskExecution;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
class TaskExecutionServiceTest extends TestCase
{
    private TaskExecutionRepositoryInterface&MockObject $taskExecutionRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private TaskRepositoryInterface&MockObject $taskRepository;
    private MessageBusInterface&MockObject $messageBus;
    private TaskExecutionService $service;

    protected function setUp(): void
    {
        $this->taskExecutionRepository = $this->createMock(TaskExecutionRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->service = new TaskExecutionService(
            $this->taskExecutionRepository,
            $this->userRepository,
            $this->taskRepository,
            $this->messageBus,
        );
    }

    private function makeUser(UserRole $role = UserRole::STUDENT): User
    {
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setFirstName('Ivan');
        $user->setLastName('Ivanov');
        $user->setPassword('hash');
        $user->setRoles([$role->value]);

        $ref = new \ReflectionClass(User::class);
        $ref->getProperty('createdAt')->setValue($user, new \DateTimeImmutable());
        $ref->getProperty('updatedAt')->setValue($user, new \DateTimeImmutable());

        return $user;
    }

    private function makeTask(int $id): Task
    {
        $task = new Task();
        $task->setTitle('Task');
        (new \ReflectionProperty(Task::class, 'id'))->setValue($task, $id);

        return $task;
    }

    private function makeTaskExecution(User $user, Task $task, int $score = 5, int $id = 1): TaskExecution
    {
        $execution = new TaskExecution($user, $task, $score);

        $ref = new \ReflectionClass(TaskExecution::class);
        $ref->getProperty('id')->setValue($execution, $id);
        $ref->getProperty('createdAt')->setValue($execution, new \DateTimeImmutable());
        $ref->getProperty('updatedAt')->setValue($execution, new \DateTimeImmutable());

        return $execution;
    }

    // --- create() ---

    public function testCreateReturnsCorrectDTO(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask(10);
        $dto = new CreateTaskExecutionRequestDTO($user->getId()->toString(), 10, 7);

        $this->userRepository->method('findById')->willReturn($user);
        $this->taskRepository->method('findOneById')->willReturn($task);
        $this->taskExecutionRepository->method('findByUserAndTask')->willReturn(null);
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));
        $this->taskExecutionRepository->expects($this->once())->method('save')
            ->willReturnCallback(function (TaskExecution $exec): void {
                $exec->_setInitialTimestamps();
                (new \ReflectionProperty(TaskExecution::class, 'id'))->setValue($exec, 1);
            });

        $result = $this->service->create($dto);

        self::assertSame($user->getId()->toString(), $result->userId);
        self::assertSame(10, $result->taskId);
        self::assertSame(7, $result->score);
    }

    public function testCreateDispatchesMessage(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask(10);
        $dto = new CreateTaskExecutionRequestDTO($user->getId()->toString(), 10, 5);

        $this->userRepository->method('findById')->willReturn($user);
        $this->taskRepository->method('findOneById')->willReturn($task);
        $this->taskExecutionRepository->method('findByUserAndTask')->willReturn(null);
        $this->taskExecutionRepository->method('save')
            ->willReturnCallback(function (TaskExecution $exec): void {
                $exec->_setInitialTimestamps();
                (new \ReflectionProperty(TaskExecution::class, 'id'))->setValue($exec, 1);
            });

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with(new RecalculateStudentLessonSkillScores($dto->userId, $dto->taskId))
            ->willReturn(new Envelope(new \stdClass()));

        $this->service->create($dto);
    }

    public function testCreateThrowsNotFoundExceptionWhenUserMissing(): void
    {
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->create(new CreateTaskExecutionRequestDTO('non-existent-uuid', 1, 5));
    }

    public function testCreateThrowsNotFoundExceptionWhenTaskMissing(): void
    {
        $user = $this->makeUser();
        $this->userRepository->method('findById')->willReturn($user);
        $this->taskRepository->method('findOneById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->create(new CreateTaskExecutionRequestDTO($user->getId()->toString(), 999, 5));
    }

    public function testCreateThrowsAlreadyExistsExceptionWhenExecutionExists(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask(1);
        $existing = $this->makeTaskExecution($user, $task);

        $this->userRepository->method('findById')->willReturn($user);
        $this->taskRepository->method('findOneById')->willReturn($task);
        $this->taskExecutionRepository->method('findByUserAndTask')->willReturn($existing);

        $this->expectException(AlreadyExistsException::class);
        $this->service->create(new CreateTaskExecutionRequestDTO($user->getId()->toString(), 1, 5));
    }

    // --- findAllForUser() ---

    public function testFindAllForUserReturnsAllForAdmin(): void
    {
        $admin = $this->makeUser(UserRole::ADMIN);
        $exec = $this->makeTaskExecution($admin, $this->makeTask(1));

        $this->taskExecutionRepository->expects($this->once())->method('findAll')->willReturn([$exec]);
        $this->taskExecutionRepository->expects($this->never())->method('findByUser');

        $result = $this->service->findAllForUser($admin);

        self::assertCount(1, $result);
    }

    public function testFindAllForUserReturnsAllForTeacher(): void
    {
        $teacher = $this->makeUser(UserRole::TEACHER);
        $exec = $this->makeTaskExecution($teacher, $this->makeTask(1));

        $this->taskExecutionRepository->expects($this->once())->method('findAll')->willReturn([$exec]);
        $this->taskExecutionRepository->expects($this->never())->method('findByUser');

        $result = $this->service->findAllForUser($teacher);

        self::assertCount(1, $result);
    }

    public function testFindAllForUserReturnsOnlyOwnForStudent(): void
    {
        $student = $this->makeUser(UserRole::STUDENT);
        $exec = $this->makeTaskExecution($student, $this->makeTask(1));

        $this->taskExecutionRepository->expects($this->once())
            ->method('findByUser')
            ->with((string) $student->getId())
            ->willReturn([$exec]);
        $this->taskExecutionRepository->expects($this->never())->method('findAll');

        $result = $this->service->findAllForUser($student);

        self::assertCount(1, $result);
    }

    // --- updateScore() ---

    public function testUpdateScoreUpdatesScoreAndDispatchesMessage(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask(5);
        $execution = $this->makeTaskExecution($user, $task, 3);

        $this->taskExecutionRepository->expects($this->once())->method('save');
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with(new RecalculateStudentLessonSkillScores($user->getId()->toString(), 5))
            ->willReturn(new Envelope(new \stdClass()));

        $dto = $this->service->updateScore($execution, 9);

        self::assertSame(9, $dto->score);
        self::assertSame(9, $execution->getScore());
    }

    // --- findById() ---

    public function testFindByIdReturnsCorrectDTO(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask(3);
        $exec = $this->makeTaskExecution($user, $task, 6, 99);

        $this->taskExecutionRepository->method('findById')->with(99)->willReturn($exec);

        $dto = $this->service->findById(99);

        self::assertSame(99, $dto->id);
        self::assertSame(6, $dto->score);
    }

    public function testFindByIdThrowsNotFoundExceptionWhenMissing(): void
    {
        $this->taskExecutionRepository->method('findById')->with(999)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->findById(999);
    }
}
