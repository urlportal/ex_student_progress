<?php

declare(strict_types=1);

namespace App\Infrastructure\Logger;

use App\Infrastructure\Http\RequestAttributes;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $record;
        }

        $requestId = $request->attributes->get(RequestAttributes::REQUEST_ID);

        if (null === $requestId) {
            return $record;
        }

        $extra = $record->extra;
        $extra[RequestAttributes::REQUEST_ID] = $requestId;

        return $record->with(extra: $extra);
    }
}
