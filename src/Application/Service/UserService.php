<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateUserRequestDTO;
use App\Application\DTO\Request\UpdateUserRequestDTO;
use App\Application\DTO\Response\UserResponseDTO;
use App\Domain\Entity\User;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(CreateUserRequestDTO $dto): UserResponseDTO
    {
        $user = (new User())
            ->setEmail($dto->email)
            ->setFirstName($dto->firstName)
            ->setLastName($dto->lastName);

        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));

        $this->userRepository->save($user);

        return $this->toResponseDTO($user);
    }

    public function findById(string $id): UserResponseDTO
    {
        $user = $this->userRepository->findById($id);

        if (null === $user) {
            throw new NotFoundException('Resource not found');
        }

        return $this->toResponseDTO($user);
    }

    /**
     * @return UserResponseDTO[]
     */
    public function findAll(): array
    {
        return array_map($this->toResponseDTO(...), $this->userRepository->findAll());
    }

    public function update(User $user, UpdateUserRequestDTO $dto): UserResponseDTO
    {
        if (null !== $dto->email && strtolower($dto->email) !== $user->getEmail()) {
            if (null !== $this->userRepository->findByEmail($dto->email)) {
                throw new DuplicateEmailException('This email is already taken.');
            }

            $user->setEmail($dto->email);
        }

        if (null !== $dto->firstName) {
            $user->setFirstName($dto->firstName);
        }

        if (null !== $dto->lastName) {
            $user->setLastName($dto->lastName);
        }

        if (null !== $dto->password) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        }

        $this->userRepository->save($user);

        return $this->toResponseDTO($user);
    }

    public function delete(User $user): void
    {
        $this->userRepository->delete($user);
    }

    private function toResponseDTO(User $user): UserResponseDTO
    {
        return new UserResponseDTO(
            id: (string) $user->getId(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
