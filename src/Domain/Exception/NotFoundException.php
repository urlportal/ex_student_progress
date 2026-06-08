<?php

namespace App\Domain\Exception;

class NotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Resource not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
