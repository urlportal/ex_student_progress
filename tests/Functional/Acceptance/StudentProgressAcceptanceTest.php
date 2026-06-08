<?php

namespace App\Tests\Functional\Acceptance;

use App\Tests\Functional\AbstractApiTestCase;

class StudentProgressAcceptanceTest extends AbstractApiTestCase
{
    public function testFullStudentProgressCycle(): void
    {
        // 1. Создаём admin и student
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        // 2. Создаём курс
        $client->request('POST', '/api/v1/courses', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Acceptance Test Course',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $courseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $courseData);
        $courseId = $courseData['id'];

        // 3. Создаём модуль
        $client->request('POST', '/api/v1/modules', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Module 1',
            'courseId' => $courseId,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $moduleData = json_decode((string) $client->getResponse()->getContent(), true);
        $moduleId = $moduleData['id'];

        // 4. Создаём урок
        $client->request('POST', '/api/v1/lessons', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Lesson 1',
            'courseId' => $courseId,
            'moduleId' => $moduleId,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $lessonData = json_decode((string) $client->getResponse()->getContent(), true);
        $lessonId = $lessonData['id'];

        // 5. Создаём 2 навыка
        $client->request('POST', '/api/v1/skills', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Skill A',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $skill1Data = json_decode((string) $client->getResponse()->getContent(), true);
        $skill1Id = $skill1Data['id'];

        $client->request('POST', '/api/v1/skills', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Skill B',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $skill2Data = json_decode((string) $client->getResponse()->getContent(), true);
        $skill2Id = $skill2Data['id'];

        // 6. Создаём задание
        $client->request('POST', '/api/v1/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Task 1',
            'lessonId' => $lessonId,
            'scoreMin' => 1,
            'scoreMax' => 10,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $taskData = json_decode((string) $client->getResponse()->getContent(), true);
        $taskId = $taskData['id'];

        // 7. Связываем навыки с весами
        $client->request('POST', '/api/v1/tasks/'.$taskId.'/skills', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'skillId' => $skill1Id,
            'weight' => 70,
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/tasks/'.$taskId.'/skills', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'skillId' => $skill2Id,
            'weight' => 30,
        ]));
        $this->assertResponseStatusCodeSame(201);

        // 8. Ставим оценку
        $client->request('POST', '/api/v1/task-executions', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'userId' => (string) $student->getId(),
            'taskId' => $taskId,
            'score' => 8,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $executionData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $executionData);
        $executionId = $executionData['id'];

        // 9. Проверяем, успешно ли установлена оценка
        $client->request('GET', '/api/v1/task-executions/'.$executionId);
        $this->assertResponseStatusCodeSame(200);
        $executionDetail = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(8, $executionDetail['score']);

        // 10. Проверяем агрегацию. Ставим score = 8. При weight 70 score = 5.6. При weight 30 score = 2.4
        $client->request('GET', '/api/v1/users/'.$student->getId().'/lessons/'.$lessonId.'/skill-scores');
        $this->assertResponseStatusCodeSame(200);
        $scores = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $scores);

        $scoreMap = [];
        foreach ($scores as $item) {
            $scoreMap[$item['skillId']] = $item['totalScore'];
        }
        $this->assertEquals(5.6, $scoreMap[$skill1Id]);
        $this->assertEquals(2.4, $scoreMap[$skill2Id]);

        // 11. Меняем оценку для этого задания на 10
        $client->request('PATCH', '/api/v1/task-executions/'.$executionId, [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'score' => 10,
        ]));
        $this->assertResponseStatusCodeSame(200);

        // 12. Проверяем пересчёт агрегации. Score = 10. При weight 70 score = 7.0. При weight 30 score = 3.0
        $client->request('GET', '/api/v1/users/'.$student->getId().'/lessons/'.$lessonId.'/skill-scores');
        $this->assertResponseStatusCodeSame(200);
        $updatedScores = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $updatedScores);

        $updatedScoreMap = [];
        foreach ($updatedScores as $item) {
            $updatedScoreMap[$item['skillId']] = $item['totalScore'];
        }
        $this->assertEquals(7.0, $updatedScoreMap[$skill1Id]);
        $this->assertEquals(3.0, $updatedScoreMap[$skill2Id]);
    }

    public function testStudentCannotCreateCourse(): void
    {
        $this->createStudent();
        $client = $this->getAuthenticatedClient('student@test.local', 'Student@12345');

        $client->request('POST', '/api/v1/courses', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'Forbidden Course',
        ]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStudentCannotViewOtherStudentTaskExecution(): void
    {
        // 1. Создаём пользователей для теста
        $this->createAdmin();
        $student1 = $this->createStudent('student1@test.local', 'Student@12345');
        $student2 = $this->createStudent('student2@test.local', 'Student@12345');

        // 2. Admin создаёт все нужные сущности
        $client = $this->getAuthenticatedClient('admin@test.local', 'Admin@12345');

        $client->request('POST', '/api/v1/courses', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'RBAC Course',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $courseId = json_decode((string) $client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/v1/lessons', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'RBAC Lesson',
            'courseId' => $courseId,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $lessonId = json_decode((string) $client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/v1/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'title' => 'RBAC Task',
            'lessonId' => $lessonId,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $taskId = json_decode((string) $client->getResponse()->getContent(), true)['id'];

        // 3. Ставим оценку для student1
        $client->request('POST', '/api/v1/task-executions', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'userId' => (string) $student1->getId(),
            'taskId' => $taskId,
            'score' => 5,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $executionId = json_decode((string) $client->getResponse()->getContent(), true)['id'];

        // 4. Аутентификация student2
        $client = $this->getAuthenticatedClient($student2->getEmail(), 'Student@12345');

        // 5. Попытка просмотреть чужую оценку
        $client->request('GET', '/api/v1/task-executions/'.$executionId);

        // 6. Ожидаем 403
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedRequestReturns401(): void
    {
        $this->client->request('GET', '/api/v1/courses');

        $this->assertResponseStatusCodeSame(401);
    }
}
