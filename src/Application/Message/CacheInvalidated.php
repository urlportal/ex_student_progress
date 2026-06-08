<?php

namespace App\Application\Message;

final readonly class CacheInvalidated
{
    public function __construct(
        public string $entity,
        /** @var string[] $keys */
        public array $keys,
        public int $timestamp,
    ) {
    }
}
