<?php

namespace App\Domain\Exception;

class InvalidRelationException extends \RuntimeException
{
    public function __construct(string $message = 'Invalid relation between entities', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
