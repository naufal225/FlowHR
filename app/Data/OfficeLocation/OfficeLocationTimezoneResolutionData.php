<?php

namespace App\Data\OfficeLocation;

class OfficeLocationTimezoneResolutionData
{
    public function __construct(
        public readonly bool $resolved,
        public readonly ?string $timezone,
        public readonly string $source,
        public readonly ?string $message = null,
    ) {}

    public static function resolved(string $timezone, string $source = 'google_maps'): self
    {
        return new self(
            resolved: true,
            timezone: $timezone,
            source: $source,
            message: null,
        );
    }

    public static function unresolved(string $message, string $source = 'unavailable'): self
    {
        return new self(
            resolved: false,
            timezone: null,
            source: $source,
            message: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'resolved' => $this->resolved,
            'timezone' => $this->timezone,
            'source' => $this->source,
            'message' => $this->message,
        ];
    }
}
