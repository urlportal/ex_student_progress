<?php

namespace App\Tests\Functional\Course;

use App\Domain\Entity\Course;
use App\Domain\Entity\Module;
use App\Tests\Functional\AbstractApiTestCase;

class CourseApiTest extends AbstractApiTestCase
{
    public function testCreateCourseReturns201(): void
    {
        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'Test Course', 'description' => 'desc'])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
    }

    public function testListCoursesReturns200(): void
    {
        $course = new Course();
        $course->setTitle('Listed Course')->setDescription('desc');
        $this->em->persist($course);
        $this->em->flush();

        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('GET', '/api/v1/courses');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetCourseByIdReturns200(): void
    {
        $course = new Course();
        $course->setTitle('Detail Course')->setDescription('desc');
        $this->em->persist($course);
        $this->em->flush();

        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('GET', '/api/v1/courses/' . $course->getId());

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
    }

    public function testUpdateCourseReturns200(): void
    {
        $course = new Course();
        $course->setTitle('Original Course')->setDescription('desc');
        $this->em->persist($course);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'PATCH',
            '/api/v1/courses/' . $course->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'Updated'])
        );

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Updated', $data['title']);
    }

    public function testDeleteCourseReturns204(): void
    {
        $course = new Course();
        $course->setTitle('Delete Course')->setDescription('desc');
        $this->em->persist($course);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request('DELETE', '/api/v1/courses/' . $course->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testCreateCourseRequiresAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'Course'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateCourseValidationEmptyTitle(): void
    {
        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => ''])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('title', $data['errors']);
    }

    public function testCreateCourseDuplicateTitleReturns409(): void
    {
        $course = new Course();
        $course->setTitle('Duplicate Course');
        $this->em->persist($course);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'Duplicate Course'])
        );

        $this->assertResponseStatusCodeSame(409);
    }

    public function testGetCourseNotFoundReturns404(): void
    {
        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('GET', '/api/v1/courses/999999');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function testDeleteCourseWithModulesReturns409(): void
    {
        $course = new Course();
        $course->setTitle('Course With Modules');
        $this->em->persist($course);

        $module = new Module();
        $module->setTitle('Module 1')->setCourse($course);
        $this->em->persist($module);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request('DELETE', '/api/v1/courses/' . $course->getId());

        $this->assertResponseStatusCodeSame(409);
    }

    public function testDeleteCourseNotFoundReturns404(): void
    {
        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request('DELETE', '/api/v1/courses/999999');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }
}
