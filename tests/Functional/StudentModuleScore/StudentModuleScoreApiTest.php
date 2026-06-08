<?php

namespace App\Tests\Functional\StudentModuleScore;

use App\Domain\Entity\AggStudentModule;
use App\Domain\Entity\Course;
use App\Domain\Entity\Module;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Tests\Functional\AbstractApiTestCase;

class StudentModuleScoreApiTest extends AbstractApiTestCase
{
    private function createTeacher(string $email = 'teacher@test.local', string $password = 'Teacher@12345'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName('Teacher')
            ->setLastName('Test')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setRoles([UserRole::TEACHER->value]);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createCourseAndModule(): array
    {
        $course = new Course();
        $course->setTitle('Test Course '.uniqid())->setDescription('desc');
        $this->em->persist($course);

        $module = new Module();
        $module->setTitle('Test Module')->setCourse($course);
        $this->em->persist($module);

        $this->em->flush();

        return [$course, $module];
    }

    public function testReturns200WithScoreForAdmin(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        [, $module] = $this->createCourseAndModule();

        $agg = new AggStudentModule($student, $module, 7.5);
        $this->em->persist($agg);
        $this->em->flush();

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/modules/%d/score', $student->getId(), $module->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($module->getId(), $data['moduleId']);
        $this->assertSame(7.5, $data['totalScore']);
    }

    public function testReturns200WithScoreForTeacher(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        [, $module] = $this->createCourseAndModule();

        $agg = new AggStudentModule($student, $module, 5.0);
        $this->em->persist($agg);
        $this->em->flush();

        $client = $this->getAuthenticatedClient('teacher@test.local', 'Teacher@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/modules/%d/score', $student->getId(), $module->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($module->getId(), $data['moduleId']);
        $this->assertSame(5.0, $data['totalScore']);
    }

    public function testReturns200WhenStudentAccessesOwnData(): void
    {
        $student = $this->createStudent();
        [, $module] = $this->createCourseAndModule();

        $agg = new AggStudentModule($student, $module, 3.0);
        $this->em->persist($agg);
        $this->em->flush();

        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/modules/%d/score', $student->getId(), $module->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($module->getId(), $data['moduleId']);
        $this->assertSame(3.0, $data['totalScore']);
    }

    public function testReturns200WithZeroWhenNoAggregation(): void
    {
        $this->createAdmin();
        $student = $this->createStudent('student2@test.local');
        [, $module] = $this->createCourseAndModule();

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/modules/%d/score', $student->getId(), $module->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($module->getId(), $data['moduleId']);
        $this->assertSame(0.0, $data['totalScore']);
    }

    public function testReturns403WhenStudentAccessesOtherStudent(): void
    {
        $student1 = $this->createStudent('student1@test.local');
        $student2 = $this->createStudent('student2@test.local');
        [, $module] = $this->createCourseAndModule();

        $client = $this->getAuthenticatedClient('student1@test.local', 'Student@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/modules/%d/score', $student2->getId(), $module->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testReturns404WhenUserNotFound(): void
    {
        $this->createAdmin();
        [, $module] = $this->createCourseAndModule();

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/00000000-0000-7000-0000-000000000000/modules/%d/score', $module->getId()));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function testReturns404WhenModuleNotFound(): void
    {
        $this->createAdmin();
        $student = $this->createStudent('student3@test.local');

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/modules/99999/score', $student->getId()));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }
}
