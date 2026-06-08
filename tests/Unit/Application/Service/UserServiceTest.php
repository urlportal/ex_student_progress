<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateUserRequestDTO;
use App\Application\DTO\Request\UpdateUserRequestDTO;
use App\Application\DTO\Response\UserResponseDTO;
use App\Application\Service\UserService;
use App\Domain\Entity\User;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class UserServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UserService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->service = new UserService(
            $this->userRepository,
            $this->passwordHasher,
        );
    }

    private function makeUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Ivan');
        $user->setLastName('Ivanov');
        $user->setPassword('hashed_password');

        $ref = new \ReflectionClass(User::class);
        $ref->getProperty('createdAt')->setValue($user, new \DateTimeImmutable());
        $ref->getProperty('updatedAt')->setValue($user, new \DateTimeImmutable());

        return $user;
    }

    // --- create() ---

    public function testCreateHashesPasswordAndSavesUser(): void
    {
        $dto = new CreateUserRequestDTO('new@test.com', 'password123', 'Ivan', 'Ivanov');

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->userRepository->expects($this->once())->method('save')
            ->willReturnCallback(function (User $u): void {
                $u->_setInitialTimestamps();
            });

        $this->service->create($dto);
    }

    public function testCreateReturnsCorrectDTO(): void
    {
        $dto = new CreateUserRequestDTO('new@test.com', 'password123', 'Ivan', 'Ivanov');

        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');
        $this->userRepository->method('save')
            ->willReturnCallback(function (User $u): void {
                $u->_setInitialTimestamps();
            });

        $result = $this->service->create($dto);

        self::assertSame('new@test.com', $result->email);
        self::assertSame('Ivan', $result->firstName);
        self::assertSame('Ivanov', $result->lastName);
        self::assertNotEmpty($result->id);
    }

    // --- findById() ---

    public function testFindByIdReturnsCorrectDTO(): void
    {
        $user = $this->makeUser('find@test.com');
        $this->userRepository->method('findById')->with((string) $user->getId())->willReturn($user);

        $dto = $this->service->findById((string) $user->getId());

        self::assertSame('find@test.com', $dto->email);
    }

    public function testFindByIdThrowsNotFoundExceptionWhenMissing(): void
    {
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->findById('non-existent-id');
    }

    // --- findAll() ---

    public function testFindAllReturnsDTOArray(): void
    {
        $u1 = $this->makeUser('a@test.com');
        $u2 = $this->makeUser('b@test.com');
        $this->userRepository->method('findAll')->willReturn([$u1, $u2]);

        $result = $this->service->findAll();

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(UserResponseDTO::class, $result);
    }

    // --- update() ---

    public function testUpdateHashesNewPassword(): void
    {
        $user = $this->makeUser();
        $dto = new UpdateUserRequestDTO(password: 'newpass123');

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'newpass123')
            ->willReturn('new_hash');
        $this->userRepository->expects($this->once())->method('save');

        $this->service->update($user, $dto);

        self::assertSame('new_hash', $user->getPassword());
    }

    public function testUpdateDoesNotOverwriteNullFields(): void
    {
        $user = $this->makeUser();
        $user->setFirstName('Ivan');
        $dto = new UpdateUserRequestDTO(lastName: 'Petrov');

        $this->userRepository->method('save');

        $this->service->update($user, $dto);

        self::assertSame('Ivan', $user->getFirstName());
        self::assertSame('Petrov', $user->getLastName());
    }

    public function testUpdateThrowsDuplicateEmailExceptionWhenEmailTaken(): void
    {
        $user = $this->makeUser('old@test.com');
        $other = $this->makeUser('taken@test.com');
        $dto = new UpdateUserRequestDTO(email: 'taken@test.com');

        $this->userRepository->method('findByEmail')->with('taken@test.com')->willReturn($other);

        $this->expectException(DuplicateEmailException::class);
        $this->service->update($user, $dto);
    }

    public function testUpdateDoesNotThrowWhenEmailSameAsCurrentIgnoringCase(): void
    {
        $user = $this->makeUser('same@test.com');
        $dto = new UpdateUserRequestDTO(email: 'SAME@test.com');

        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->userRepository->method('save');

        // Should not throw DuplicateEmailException
        $this->service->update($user, $dto);
        self::assertTrue(true);
    }

    // --- delete() ---

    public function testDeleteCallsRepositoryDelete(): void
    {
        $user = $this->makeUser();
        $this->userRepository->expects($this->once())->method('delete')->with($user);

        $this->service->delete($user);
    }
}
