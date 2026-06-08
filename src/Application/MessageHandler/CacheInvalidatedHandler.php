<?php

namespace App\Application\MessageHandler;

use App\Application\Message\CacheInvalidated;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
#[WithMonologChannel('app')]
final class CacheInvalidatedHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CacheInvalidated $message): void
    {
        $this->logger->info('Cache invalidation event received', [
            'entity' => $message->entity,
            'keys' => $message->keys,
            'timestamp' => $message->timestamp,
        ]);
    }
}
