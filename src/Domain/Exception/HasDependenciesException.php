<?php

namespace App\Domain\Exception;

class HasDependenciesException extends \RuntimeException
{
    public function __construct(string $message = 'Resource has dependencies and cannot be deleted', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
