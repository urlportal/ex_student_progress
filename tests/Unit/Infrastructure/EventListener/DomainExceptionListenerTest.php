<?php

namespace App\Tests\Unit\Infrastructure\EventListener;

use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\InvalidRelationException;
use App\Domain\Exception\NotFoundException;
use App\Infrastructure\EventListener\DomainExceptionListener;
use Doctrine\DBAL\Exception\DriverException as DBALDriverException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AllowMockObjectsWithoutExpectations]
class DomainExceptionListenerTest extends TestCase
{
    private DomainExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new DomainExceptionListener();
    }

    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testNotFoundExceptionReturns404(): void
    {
        $event = $this->createEvent(new NotFoundException('Resource X not found'));
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(404, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame('Resource X not found', $data['message']);
    }

    public function testDuplicateEmailExceptionReturns422(): void
    {
        $event = $this->createEvent(new DuplicateEmailException('Email exists'));
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(422, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame(['email' => ['Email exists']], $data['errors']);
    }

    public function testAlreadyExistsExceptionReturns409(): void
    {
        $event = $this->createEvent(new AlreadyExistsException('Already exists'));
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(409, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame('Already exists', $data['message']);
    }

    public function testHasDependenciesExceptionReturns409(): void
    {
        $event = $this->createEvent(new HasDependenciesException('Has deps'));
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(409, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame('Has deps', $data['message']);
    }

    public function testInvalidRelationExceptionReturns422(): void
    {
        $event = $this->createEvent(new InvalidRelationException('Invalid relation'));
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(422, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame('Invalid relation', $data['message']);
    }

    public function testDbalDriverExceptionWithSqlState22003Returns404(): void
    {
        $mock = $this->createMock(DBALDriverException::class);
        $mock->method('getSQLState')->willReturn('22003');

        $event = $this->createEvent($mock);
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(404, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame('Resource not found', $data['message']);
    }

    public function testDbalDriverExceptionWithOtherSqlStateDoesNotSetResponse(): void
    {
        $mock = $this->createMock(DBALDriverException::class);
        $mock->method('getSQLState')->willReturn('99999');

        $event = $this->createEvent($mock);
        ($this->listener)($event);

        self::assertNull($event->getResponse());
    }

    public function testUnprocessableEntityWithValidationFailedExceptionReturns422(): void
    {
        $violation = new ConstraintViolation('message', null, [], null, 'field', null);
        $violations = new ConstraintViolationList([$violation]);
        $validationEx = new ValidationFailedException(null, $violations);
        $httpEx = new UnprocessableEntityHttpException(previous: $validationEx);

        $event = $this->createEvent($httpEx);
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(422, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame(['field' => ['message']], $data['errors']);
    }

    public function testUnprocessableEntityWithoutValidationFailedExceptionDoesNotSetResponse(): void
    {
        $httpEx = new UnprocessableEntityHttpException();

        $event = $this->createEvent($httpEx);
        ($this->listener)($event);

        self::assertNull($event->getResponse());
    }

    public function testRuntimeExceptionDoesNotSetResponse(): void
    {
        $event = $this->createEvent(new \RuntimeException('Some error'));
        ($this->listener)($event);

        self::assertNull($event->getResponse());
    }

    public function testMultipleViolationsOnSameFieldAllIncludedInResponse(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('msg1', null, [], null, 'field', null),
            new ConstraintViolation('msg2', null, [], null, 'field', null),
        ]);
        $validationEx = new ValidationFailedException(null, $violations);
        $httpEx = new UnprocessableEntityHttpException(previous: $validationEx);

        $event = $this->createEvent($httpEx);
        ($this->listener)($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(422, $event->getResponse()->getStatusCode());
        $data = json_decode((string) $event->getResponse()->getContent(), true);
        self::assertSame(['field' => ['msg1', 'msg2']], $data['errors']);
    }
}
