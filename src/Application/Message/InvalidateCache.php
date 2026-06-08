<?php

namespace App\Application\Message;

final readonly class InvalidateCache
{
    public function __construct(
        public string $cachePool,
        /** @var string[] $cacheKeys */
        public array $cacheKeys,
        public string $source,
    ) {
    }
}
