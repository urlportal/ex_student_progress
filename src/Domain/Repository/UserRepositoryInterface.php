<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function save(User $user): void;

    public function findById(string $id): ?User;

    /** @return User[] */
    public function findAll(): array;

    /** @return list<User> */
    public function findByRole(UserRole $role): array;

    public function delete(User $user): void;
}
