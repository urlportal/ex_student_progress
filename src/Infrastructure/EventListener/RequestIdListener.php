<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Infrastructure\Http\RequestAttributes;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Uid\Uuid;

final class RequestIdListener
{
    public const string HEADER_NAME = 'X-Request-Id';

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $requestId = $request->headers->get(self::HEADER_NAME);
        if (null === $requestId || '' === $requestId) {
            $requestId = Uuid::v4()->toRfc4122();
        }

        $request->attributes->set(RequestAttributes::REQUEST_ID, $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $requestId = $event->getRequest()->attributes->get(RequestAttributes::REQUEST_ID);

        if (null !== $requestId) {
            $event->getResponse()->headers->set(self::HEADER_NAME, $requestId);
        }
    }
}
