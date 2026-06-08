<?php

namespace App\Infrastructure\EventListener;

use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\InvalidRelationException;
use App\Domain\Exception\NotFoundException;
use Doctrine\DBAL\Exception\DriverException as DBALDriverException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundException) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], 404));

            return;
        }

        if ($exception instanceof DuplicateEmailException) {
            $event->setResponse(new JsonResponse(['errors' => ['email' => [$exception->getMessage()]]], 422));

            return;
        }

        if ($exception instanceof AlreadyExistsException || $exception instanceof HasDependenciesException) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], 409));

            return;
        }

        // Например, если попытаться создать урок с привязкой к курсу и модулю, но
        // модуль при этом не имеет отношения к данному курсу (такого быть не должно).
        if ($exception instanceof InvalidRelationException) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], 422));

            return;
        }

        if ($exception instanceof DBALDriverException && '22003' === $exception->getSQLState()) {
            $event->setResponse(new JsonResponse(['message' => 'Resource not found'], 404));

            return;
        }

        if ($exception instanceof UnprocessableEntityHttpException) {
            $previous = $exception->getPrevious();

            if ($previous instanceof ValidationFailedException) {
                $errors = [];
                foreach ($previous->getViolations() as $violation) {
                    $field = $violation->getPropertyPath();
                    $errors[$field][] = $violation->getMessage();
                }
                $event->setResponse(new JsonResponse(['errors' => $errors], 422));
            }
        }
    }
}
