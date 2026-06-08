<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\StudentModuleScoreResponseDTO;
use App\Application\Service\AggStudentModuleService;
use App\Domain\Entity\AggStudentModule;
use App\Domain\Entity\Module;
use App\Domain\Entity\User;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\AggStudentModuleRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class AggStudentModuleServiceTest extends TestCase
{
    private AggStudentModuleRepositoryInterface&MockObject $aggRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private ModuleRepositoryInterface&MockObject $moduleRepository;
    private CacheInterface&MockObject $cache;
    private AggStudentModuleService $service;

    protected function setUp(): void
    {
        $this->aggRepository = $this->createMock(AggStudentModuleRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->moduleRepository = $this->createMock(ModuleRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->service = new AggStudentModuleService(
            $this->aggRepository,
            $this->userRepository,
            $this->moduleRepository,
            $this->cache,
        );
    }

    public function testGetReturnsEmptyDtoWhenNoAggregationExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentModuleScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->moduleRepository->method('findById')->willReturn($this->createMock(Module::class));
        $this->aggRepository->method('findByStudentAndModule')->willReturn(null);

        $result = $this->service->getByStudentAndModule('uuid', 42);

        self::assertInstanceOf(StudentModuleScoreResponseDTO::class, $result);
        self::assertSame(42, $result->moduleId);
        self::assertSame(0.0, $result->totalScore);
    }

    public function testGetReturnsDtoFromEntity(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentModuleScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(42);
        $this->moduleRepository->method('findById')->willReturn($module);

        $entity = $this->createMock(AggStudentModule::class);
        $entity->method('getModule')->willReturn($module);
        $entity->method('getTotalScore')->willReturn(7.5);
        $this->aggRepository->method('findByStudentAndModule')->willReturn($entity);

        $result = $this->service->getByStudentAndModule('uuid', 42);

        self::assertInstanceOf(StudentModuleScoreResponseDTO::class, $result);
        self::assertSame(42, $result->moduleId);
        self::assertSame(7.5, $result->totalScore);
    }

    public function testGetThrowsNotFoundWhenStudentNotExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentModuleScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndModule('uuid', 42);
    }

    public function testGetThrowsNotFoundWhenModuleNotExists(): void
    {
        $this->cache->method('get')->willReturnCallback(
            function (string $key, callable $callback): StudentModuleScoreResponseDTO {
                return $callback($this->createMock(ItemInterface::class));
            }
        );
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->moduleRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndModule('uuid', 42);
    }

    public function testGetFallsBackToRepositoryWhenCacheThrows(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));

        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(42);
        $this->moduleRepository->method('findById')->willReturn($module);

        $entity = $this->createMock(AggStudentModule::class);
        $entity->method('getModule')->willReturn($module);
        $entity->method('getTotalScore')->willReturn(7.5);
        $this->aggRepository->method('findByStudentAndModule')->willReturn($entity);

        $result = $this->service->getByStudentAndModule('uuid', 42);

        self::assertInstanceOf(StudentModuleScoreResponseDTO::class, $result);
        self::assertSame(42, $result->moduleId);
        self::assertSame(7.5, $result->totalScore);
    }

    public function testFallbackThrowsNotFoundWhenStudentNotExists(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndModule('uuid', 42);
    }

    public function testFallbackThrowsNotFoundWhenModuleNotExists(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache error'));
        $this->userRepository->method('findById')->willReturn($this->createMock(User::class));
        $this->moduleRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getByStudentAndModule('uuid', 42);
    }
}
