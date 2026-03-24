<?php

namespace App\Exceptions\Attendance;

use Exception;

class AttendanceException extends Exception
{
    public function __construct(
        string $message = 'Terjadi kesalahan pada proses absensi.',
        protected string $errorCode = 'ATTENDANCE_ERROR',
        protected int $statusCode = 422,
        protected array $context = [],
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode(),
            'context' => $this->getContext(),
        ];
    }
}
