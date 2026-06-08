<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateSkillRequestDTO;
use App\Application\DTO\Request\UpdateSkillRequestDTO;
use App\Domain\Entity\Skill;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\SkillRepositoryInterface;

class SkillService
{
    public function __construct(
        private readonly SkillRepositoryInterface $skillRepository,
    ) {
    }

    public function find(int $id): Skill
    {
        $skill = $this->skillRepository->findById($id);

        if (null === $skill) {
            throw new NotFoundException();
        }

        return $skill;
    }

    /** @return Skill[] */
    public function findAll(): array
    {
        return $this->skillRepository->findAll();
    }

    public function create(CreateSkillRequestDTO $dto): Skill
    {
        $existing = $this->skillRepository->findByTitle($dto->title);

        if (null !== $existing) {
            throw new AlreadyExistsException('Skill already exists');
        }

        $skill = new Skill();
        $skill->setTitle($dto->title);

        $this->skillRepository->save($skill);

        return $skill;
    }

    public function update(int $id, UpdateSkillRequestDTO $dto): Skill
    {
        $skill = $this->find($id);

        if (null !== $dto->title) {
            $existingByTitle = $this->skillRepository->findByTitle($dto->title);

            if (null !== $existingByTitle && $existingByTitle->getId() !== $skill->getId()) {
                throw new AlreadyExistsException('Skill already exists');
            }

            $skill->setTitle($dto->title);
        }

        $this->skillRepository->save($skill);

        return $skill;
    }

    public function delete(int $id): void
    {
        $skill = $this->find($id);

        if ($skill->getSkillTasks()->count() > 0) {
            throw new HasDependenciesException('Cannot delete skill with linked tasks');
        }

        $this->skillRepository->delete($skill);
    }
}
