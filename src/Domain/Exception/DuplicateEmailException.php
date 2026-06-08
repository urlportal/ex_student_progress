<?php

namespace App\Domain\Exception;

class DuplicateEmailException extends \RuntimeException
{
    public function __construct(string $message = 'Email already exists', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
