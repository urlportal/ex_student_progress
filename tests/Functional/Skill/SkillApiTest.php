<?php

namespace App\Tests\Functional\Skill;

use App\Domain\Entity\Skill;
use App\Tests\Functional\AbstractApiTestCase;

class SkillApiTest extends AbstractApiTestCase
{
    public function testCreateSkillReturns201(): void
    {
        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'POST',
            '/api/v1/skills',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'PHP'])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
    }

    public function testListSkillsReturns200(): void
    {
        $skill = new Skill();
        $skill->setTitle('Listed Skill');
        $this->em->persist($skill);
        $this->em->flush();

        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('GET', '/api/v1/skills');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetSkillByIdReturns200(): void
    {
        $skill = new Skill();
        $skill->setTitle('Detail Skill');
        $this->em->persist($skill);
        $this->em->flush();

        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('GET', '/api/v1/skills/' . $skill->getId());

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
    }

    public function testUpdateSkillReturns200(): void
    {
        $skill = new Skill();
        $skill->setTitle('Original Skill');
        $this->em->persist($skill);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'PATCH',
            '/api/v1/skills/' . $skill->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'Updated'])
        );

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Updated', $data['title']);
    }

    public function testDeleteSkillReturns204(): void
    {
        $skill = new Skill();
        $skill->setTitle('Delete Skill');
        $this->em->persist($skill);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request('DELETE', '/api/v1/skills/' . $skill->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testCreateSkillDuplicateTitleReturns409(): void
    {
        $skill = new Skill();
        $skill->setTitle('Duplicate Skill');
        $this->em->persist($skill);
        $this->em->flush();

        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'POST',
            '/api/v1/skills',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['title' => 'Duplicate Skill'])
        );

        $this->assertResponseStatusCodeSame(409);
    }

    public function testCreateSkillValidationEmptyTitleReturns422(): void
    {
        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request(
            'POST',
            '/api/v1/skills',
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

    public function testGetSkillNotFoundReturns404(): void
    {
        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('GET', '/api/v1/skills/999999');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function testDeleteSkillNotFoundReturns404(): void
    {
        $this->createAdmin();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request('DELETE', '/api/v1/skills/999999');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Resource not found', $data['message']);
    }
}
