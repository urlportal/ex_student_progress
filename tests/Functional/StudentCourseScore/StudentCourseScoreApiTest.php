<?php

namespace App\Tests\Functional\StudentCourseScore;

use App\Domain\Entity\AggStudentCourse;
use App\Domain\Entity\Course;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Tests\Functional\AbstractApiTestCase;

class StudentCourseScoreApiTest extends AbstractApiTestCase
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

    private function createCourse(): Course
    {
        $course = new Course();
        $course->setTitle('Test Course '.uniqid())->setDescription('desc');
        $this->em->persist($course);
        $this->em->flush();

        return $course;
    }

    public function testReturns200WithScoreForAdmin(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $course = $this->createCourse();

        $agg = new AggStudentCourse($student, $course, 42.0);
        $this->em->persist($agg);
        $this->em->flush();

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/courses/%d/score', $student->getId(), $course->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($course->getId(), $data['courseId']);
        $this->assertSame(42.0, $data['totalScore']);
    }

    public function testReturns200WithScoreForTeacher(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $course = $this->createCourse();

        $agg = new AggStudentCourse($student, $course, 15.0);
        $this->em->persist($agg);
        $this->em->flush();

        $client = $this->getAuthenticatedClient('teacher@test.local', 'Teacher@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/courses/%d/score', $student->getId(), $course->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($course->getId(), $data['courseId']);
        $this->assertSame(15.0, $data['totalScore']);
    }

    public function testReturns200WhenStudentAccessesOwnData(): void
    {
        $student = $this->createStudent();
        $course = $this->createCourse();

        $agg = new AggStudentCourse($student, $course, 8.0);
        $this->em->persist($agg);
        $this->em->flush();

        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/courses/%d/score', $student->getId(), $course->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($course->getId(), $data['courseId']);
        $this->assertSame(8.0, $data['totalScore']);
    }

    public function testReturns200WithZeroWhenNoAggregation(): void
    {
        $this->createAdmin();
        $student = $this->createStudent('student2@test.local');
        $course = $this->createCourse();

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/courses/%d/score', $student->getId(), $course->getId()));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($course->getId(), $data['courseId']);
        $this->assertSame(0.0, $data['totalScore']);
    }

    public function testReturns403WhenStudentAccessesOtherStudent(): void
    {
        $student1 = $this->createStudent('student1@test.local');
        $student2 = $this->createStudent('student2@test.local');
        $course = $this->createCourse();

        $client = $this->getAuthenticatedClient('student1@test.local', 'Student@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/courses/%d/score', $student2->getId(), $course->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testReturns404WhenUserNotFound(): void
    {
        $this->createAdmin();
        $course = $this->createCourse();

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/00000000-0000-7000-0000-000000000000/courses/%d/score', $course->getId()));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function testReturns404WhenCourseNotFound(): void
    {
        $this->createAdmin();
        $student = $this->createStudent('student3@test.local');

        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');
        $client->request('GET', \sprintf('/api/v1/users/%s/courses/99999/score', $student->getId()));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }
}
