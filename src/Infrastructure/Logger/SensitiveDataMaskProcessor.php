<?php

declare(strict_types=1);

namespace App\Infrastructure\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class SensitiveDataMaskProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'secret',
        'token',
        'api_key',
        'authorization',
        'access_token',
        'refresh_token',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->maskSensitiveData($record->context),
            extra: $this->maskSensitiveData($record->extra),
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function maskSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif (\is_string($key) && \in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
