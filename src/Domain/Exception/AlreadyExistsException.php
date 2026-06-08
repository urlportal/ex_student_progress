<?php

namespace App\Domain\Exception;

class AlreadyExistsException extends \RuntimeException
{
    public function __construct(string $message = 'Resource already exists', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
