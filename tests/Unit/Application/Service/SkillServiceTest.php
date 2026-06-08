<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateSkillRequestDTO;
use App\Application\DTO\Request\UpdateSkillRequestDTO;
use App\Application\Service\SkillService;
use App\Domain\Entity\Skill;
use App\Domain\Entity\Task;
use App\Domain\Entity\TaskSkill;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\SkillRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SkillServiceTest extends TestCase
{
    private SkillRepositoryInterface&MockObject $skillRepository;
    private SkillService $service;

    protected function setUp(): void
    {
        $this->skillRepository = $this->createMock(SkillRepositoryInterface::class);
        $this->service = new SkillService($this->skillRepository);
    }

    private function makeSkill(int $id, string $title = 'PHP'): Skill
    {
        $skill = new Skill();
        $skill->setTitle($title);
        (new \ReflectionProperty(Skill::class, 'id'))->setValue($skill, $id);

        return $skill;
    }

    // --- find() ---

    public function testFindReturnsSkill(): void
    {
        $skill = $this->makeSkill(1);
        $this->skillRepository->method('findById')->with(1)->willReturn($skill);

        $result = $this->service->find(1);

        self::assertSame($skill, $result);
    }

    public function testFindThrowsNotFoundExceptionWhenSkillDoesNotExist(): void
    {
        $this->skillRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->find(99);
    }

    // --- findAll() ---

    public function testFindAllReturnsSkillList(): void
    {
        $skill = $this->makeSkill(1);
        $this->skillRepository->method('findAll')->willReturn([$skill]);

        $result = $this->service->findAll();

        self::assertSame([$skill], $result);
    }

    // --- create() ---

    public function testCreateSavesAndReturnsSkillWhenTitleIsUnique(): void
    {
        $this->skillRepository->method('findByTitle')->with('PHP')->willReturn(null);
        $this->skillRepository->expects($this->once())->method('save');

        $result = $this->service->create(new CreateSkillRequestDTO(title: 'PHP'));

        self::assertSame('PHP', $result->getTitle());
    }

    public function testCreateThrowsAlreadyExistsExceptionWhenTitleTaken(): void
    {
        $existing = $this->makeSkill(1, 'PHP');
        $this->skillRepository->method('findByTitle')->with('PHP')->willReturn($existing);

        $this->expectException(AlreadyExistsException::class);
        $this->service->create(new CreateSkillRequestDTO(title: 'PHP'));
    }

    // --- update() ---

    public function testUpdateSavesSkillWhenTitleIsUnique(): void
    {
        $skill = $this->makeSkill(1, 'PHP');
        $this->skillRepository->method('findById')->with(1)->willReturn($skill);
        $this->skillRepository->method('findByTitle')->with('Go')->willReturn(null);
        $this->skillRepository->expects($this->once())->method('save');

        $result = $this->service->update(1, new UpdateSkillRequestDTO(title: 'Go'));

        self::assertSame('Go', $result->getTitle());
    }

    public function testUpdateThrowsAlreadyExistsExceptionWhenNewTitleTaken(): void
    {
        $skill = $this->makeSkill(1, 'PHP');
        $other = $this->makeSkill(2, 'Go');
        $this->skillRepository->method('findById')->with(1)->willReturn($skill);
        $this->skillRepository->method('findByTitle')->with('Go')->willReturn($other);

        $this->expectException(AlreadyExistsException::class);
        $this->service->update(1, new UpdateSkillRequestDTO(title: 'Go'));
    }

    public function testUpdateThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->skillRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->update(99, new UpdateSkillRequestDTO(title: 'PHP'));
    }

    // --- delete() ---

    public function testDeleteCallsRepositoryWhenSkillHasNoTasks(): void
    {
        $skill = $this->makeSkill(1);
        $this->skillRepository->method('findById')->with(1)->willReturn($skill);
        $this->skillRepository->expects($this->once())->method('delete')->with($skill);

        $this->service->delete(1);
    }

    public function testDeleteThrowsHasDependenciesExceptionWhenSkillHasTasks(): void
    {
        $task = $this->createMock(Task::class);
        $taskSkill = new TaskSkill($task, new Skill(), 1.0);

        $skill = $this->createMock(Skill::class);
        $skill->method('getSkillTasks')->willReturn(new ArrayCollection([$taskSkill]));
        $skill->method('getId')->willReturn(1);
        $this->skillRepository->method('findById')->with(1)->willReturn($skill);

        $this->expectException(HasDependenciesException::class);
        $this->service->delete(1);
    }

    public function testDeleteThrowsNotFoundExceptionWhenIdNotFound(): void
    {
        $this->skillRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->delete(99);
    }
}
