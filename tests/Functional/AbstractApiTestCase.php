<?php

namespace App\Tests\Functional;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AbstractApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    protected UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function createAdmin(string $email = 'admin@test.local', string $password = 'Admin@12345'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName('Admin')
            ->setLastName('Test')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setRoles([UserRole::ADMIN->value]);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createStudent(string $email = 'student@test.local', string $password = 'Student@12345'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName('Student')
            ->setLastName('Test')
            ->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function getAuthenticatedClient(string $email, string $password): KernelBrowser
    {
        $this->client->request(
            'POST',
            '/api/auth/token',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['email' => $email, 'password' => $password])
        );
        /** @var array{token: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$data['token']);

        return $this->client;
    }
}
