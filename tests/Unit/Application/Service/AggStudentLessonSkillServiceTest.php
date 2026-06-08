<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\StudentLessonSkillScoreResponseDTO;
use App\Application\Service\AggStudentLessonSkillService;
use App\Domain\Entity\AggStudentLessonSkill;
use App\Domain\Entity\Lesson;
use App\Domain\Entity\Skill;
use App\Domain\Entity\User;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\AggStudentLessonSkillRepositoryInterface;
use App\Domain\Repository\LessonRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class AggStudentLessonSkillServiceTest extends TestCase
{
    private AggStudentLessonSkillRepositoryInterface&MockObject $aggRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private LessonRepositoryInterface&MockObject $lessonRepository;
    private CacheInterface&MockObject $cache;
    private AggStudentLessonSkillService $service;

    protected function setUp(): void
    {
        $this->aggRepository = $this->createMock(AggStudentLessonSkillRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->service = new AggStudentLessonSkillService(
            $this->aggRepository,
            $this->userRepository,
            $this->lessonRepository,
            $this->cache,
        );
    }

    public function testGetByStudentAndLessonReturnsCachedData(): void
    {
        $dto = new StudentLessonSkillScoreResponseDTO(skillId: 1, skillTitle: 'PHP', totalScore: 10.0);
        $this->cache->method('get')->willReturn([$dto]);
        $this->aggRepository->expects($this->never())->method('findByStudentAndLesson');

        $result = $this->service->getByStudentAndLesson('uuid', 1);

        self::assertSame([$dto], $result);
    }

    public function testGetByStudentAndLessonFetchesFromRepositoryOnCacheMiss(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): array {
                return $callback($this->createMock(ItemInterface::class));
            }
        );

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->lessonRepository->method('findById')->willReturn($this->createMock(Lesson::class));

        $skill = $this->createMock(Skill::class);
        $skill->method('getId')->willReturn(1);
        $skill->method('getTitle')->willReturn('PHP');

        $aggEntity = $this->createMock(AggStudentLessonSkill::class);
        $aggEntity->method('getSkill')->willReturn($skill);
        $aggEntity->method('getTotalScore')->willReturn(10.0);

        $this->aggRepository->method('findByStudentAndLesson')->willReturn([$aggEntity]);

        $result = $this->service->getByStudentAndLesson('uuid', 1);

        self::assertCount(1, $result);
        self::assertInstanceOf(StudentLessonSkillScoreResponseDTO::class, $result[0]);
        self::assertSame(1, $result[0]->skillId);
        self::assertSame('PHP', $result[0]->skillTitle);
        self::assertSame(10.0, $result[0]->totalScore);
    }

    public function testGetByStudentAndLessonThrowsNotFoundExceptionWhenStudentNotExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): array {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->getByStudentAndLesson('uuid', 1);
    }

    public function testGetByStudentAndLessonThrowsNotFoundExceptionWhenLessonNotExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): array {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->lessonRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Lesson not found');

        $this->service->getByStudentAndLesson('uuid', 1);
    }

    public function testGetByStudentAndLessonFallsBackToRepositoryWhenCacheThrows(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->lessonRepository->method('findById')->willReturn($this->createMock(Lesson::class));

        $skill = $this->createMock(Skill::class);
        $skill->method('getId')->willReturn(1);
        $skill->method('getTitle')->willReturn('PHP');

        $aggEntity = $this->createMock(AggStudentLessonSkill::class);
        $aggEntity->method('getSkill')->willReturn($skill);
        $aggEntity->method('getTotalScore')->willReturn(10.0);

        $this->aggRepository->method('findByStudentAndLesson')->willReturn([$aggEntity]);

        $result = $this->service->getByStudentAndLesson('uuid', 1);

        self::assertCount(1, $result);
        self::assertInstanceOf(StudentLessonSkillScoreResponseDTO::class, $result[0]);
        self::assertSame(1, $result[0]->skillId);
        self::assertSame('PHP', $result[0]->skillTitle);
        self::assertSame(10.0, $result[0]->totalScore);
    }

    public function testFallbackThrowsNotFoundExceptionWhenStudentNotExists(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->getByStudentAndLesson('uuid', 1);
    }

    public function testFallbackThrowsNotFoundExceptionWhenLessonNotExists(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->lessonRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Lesson not found');

        $this->service->getByStudentAndLesson('uuid', 1);
    }
}
