<?php

namespace App\Application\MessageHandler;

use App\Application\Message\InvalidateCache;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
#[WithMonologChannel('app')]
final class InvalidateCacheHandler
{
    public function __construct(
        #[Target('aggregationCache')] private readonly CacheInterface $aggregationCache,
        #[Target('listsCache')] private readonly CacheInterface $listsCache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(InvalidateCache $message): void
    {
        $pool = match ($message->cachePool) {
            'aggregation_cache' => $this->aggregationCache,
            'lists_cache' => $this->listsCache,
            default => throw new \InvalidArgumentException(\sprintf('Unknown cache pool: %s', $message->cachePool)),
        };

        foreach ($message->cacheKeys as $key) {
            try {
                $pool->delete($key);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to delete cache key', [
                    'key' => $key,
                    'pool' => $message->cachePool,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        $this->logger->info('Cache keys invalidated', [
            'keys' => $message->cacheKeys,
            'pool' => $message->cachePool,
            'source' => $message->source,
        ]);
    }
}
