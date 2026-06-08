<?php

namespace App\Tests\Unit\Application\MessageHandler;

use App\Application\Message\InvalidateCache;
use App\Application\Message\RecalculateStudentLessonSkillScores;
use App\Application\MessageHandler\RecalculateStudentLessonSkillScoresHandler;
use App\Domain\Entity\Course;
use App\Domain\Entity\Lesson;
use App\Domain\Entity\Module;
use App\Domain\Entity\Task;
use App\Domain\Entity\User;
use App\Domain\Repository\AggStudentCourseRepositoryInterface;
use App\Domain\Repository\AggStudentLessonSkillRepositoryInterface;
use App\Domain\Repository\AggStudentModuleRepositoryInterface;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
class RecalculateStudentLessonSkillScoresHandlerTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $taskRepository;
    private TaskExecutionRepositoryInterface&MockObject $taskExecutionRepository;
    private AggStudentLessonSkillRepositoryInterface&MockObject $aggRepository;
    private AggStudentModuleRepositoryInterface&MockObject $aggModuleRepository;
    private AggStudentCourseRepositoryInterface&MockObject $aggCourseRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private MessageBusInterface&MockObject $bus;
    private LoggerInterface&MockObject $logger;
    private RecalculateStudentLessonSkillScoresHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->taskExecutionRepository = $this->createMock(TaskExecutionRepositoryInterface::class);
        $this->aggRepository = $this->createMock(AggStudentLessonSkillRepositoryInterface::class);
        $this->aggModuleRepository = $this->createMock(AggStudentModuleRepositoryInterface::class);
        $this->aggCourseRepository = $this->createMock(AggStudentCourseRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new RecalculateStudentLessonSkillScoresHandler(
            $this->taskRepository,
            $this->taskExecutionRepository,
            $this->aggRepository,
            $this->aggModuleRepository,
            $this->aggCourseRepository,
            $this->userRepository,
            $this->bus,
            $this->logger,
        );
    }

    public function testHandlerUpsertsBothModuleAndCourseWhenLessonHasModule(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(10);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn($module);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndModule')->willReturn('50');
        $this->taskExecutionRepository->method('sumScoreByStudentAndCourse')->willReturn('80');
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->aggModuleRepository->expects($this->once())->method('upsert')
            ->with('student-uuid', 10, 50.0);

        $this->aggCourseRepository->expects($this->once())->method('upsert')
            ->with('student-uuid', 20, 80.0);

        ($this->handler)($message);
    }

    public function testHandlerExitsEarlyWhenTaskNotFound(): void
    {
        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn(null);

        $this->aggModuleRepository->expects($this->never())->method('upsert');
        $this->aggCourseRepository->expects($this->never())->method('upsert');

        ($this->handler)(new RecalculateStudentLessonSkillScores('student-uuid', 999));
    }

    public function testHandlerExitsEarlyWhenStudentNotFound(): void
    {
        $task = $this->createMock(Task::class);
        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn(null);

        $this->aggModuleRepository->expects($this->never())->method('upsert');
        $this->aggCourseRepository->expects($this->never())->method('upsert');

        ($this->handler)(new RecalculateStudentLessonSkillScores('student-uuid', 1));
    }

    public function testUpsertExceptionPropagatesOutOfHandler(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(10);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn($module);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndModule')->willReturn('50');

        $this->aggModuleRepository->method('upsert')->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        ($this->handler)($message);
    }

    public function testHandlerUpsertsModuleWithZeroScore(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(10);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn($module);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndModule')->willReturn('0');
        $this->taskExecutionRepository->method('sumScoreByStudentAndCourse')->willReturn('0');
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->aggModuleRepository->expects($this->once())->method('upsert')
            ->with('student-uuid', 10, 0.0);

        ($this->handler)($message);
    }

    public function testHandlerSkipsModuleUpsertWhenLessonHasNoModule(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn(null);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndCourse')->willReturn('30');
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->aggModuleRepository->expects($this->never())->method('upsert');
        $this->aggCourseRepository->expects($this->once())->method('upsert')
            ->with('student-uuid', 20, 30.0);

        ($this->handler)($message);
    }

    public function testCacheDispatchedWithThreeKeysWhenModulePresent(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(10);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn($module);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndModule')->willReturn('50');
        $this->taskExecutionRepository->method('sumScoreByStudentAndCourse')->willReturn('80');

        $this->bus->expects($this->once())->method('dispatch')
            ->with($this->callback(function (InvalidateCache $msg): bool {
                return 3 === \count($msg->cacheKeys)
                    && \in_array('agg_student_lesson_skill_student-uuid_5', $msg->cacheKeys, true)
                    && \in_array('agg_student_module_student-uuid_10', $msg->cacheKeys, true)
                    && \in_array('agg_student_course_student-uuid_20', $msg->cacheKeys, true);
            }))
            ->willReturn(new Envelope(new \stdClass()));

        ($this->handler)($message);
    }

    public function testCacheDispatchedWithTwoKeysWhenNoModule(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn(null);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndCourse')->willReturn('80');

        $this->bus->expects($this->once())->method('dispatch')
            ->with($this->callback(function (InvalidateCache $msg): bool {
                return 2 === \count($msg->cacheKeys)
                    && \in_array('agg_student_lesson_skill_student-uuid_5', $msg->cacheKeys, true)
                    && \in_array('agg_student_course_student-uuid_20', $msg->cacheKeys, true)
                    && !\in_array('agg_student_module_student-uuid_', $msg->cacheKeys, true);
            }))
            ->willReturn(new Envelope(new \stdClass()));

        ($this->handler)($message);
    }

    public function testCacheInvalidationFailureIsLoggedAndNotPropagated(): void
    {
        $message = new RecalculateStudentLessonSkillScores('student-uuid', 1);

        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(20);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(5);
        $lesson->method('getModule')->willReturn(null);
        $lesson->method('getCourse')->willReturn($course);
        $lesson->method('getTasks')->willReturn(new ArrayCollection());

        $task = $this->createMock(Task::class);
        $task->method('getLesson')->willReturn($lesson);

        $this->taskRepository->method('findOneByIdWithLessonTasksAndSkills')->willReturn($task);
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->taskExecutionRepository->method('findByUserAndLesson')->willReturn([]);
        $this->taskExecutionRepository->method('sumScoreByStudentAndCourse')->willReturn('80');

        $this->bus->method('dispatch')->willThrowException(new \RuntimeException('Bus error'));
        $this->logger->expects($this->once())->method('warning')
            ->with('Cache invalidation dispatch failed', $this->arrayHasKey('error'));

        ($this->handler)($message);
    }
}
