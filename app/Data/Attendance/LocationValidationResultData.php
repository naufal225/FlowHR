<?php

namespace App\Data\Attendance;

class LocationValidationResultData
{
    public function __construct(
        public readonly bool $isValid,
        public readonly bool $isSuspicious,
        public readonly float $distanceMeter,
        public readonly float $accuracyMeter,
        public readonly ?string $reason = null,
    ) {}

    public static function valid(
        float $distanceMeter,
        float $accuracyMeter,
        ?string $reason = null,
    ): self {
        return new self(
            isValid: true,
            isSuspicious: false,
            distanceMeter: $distanceMeter,
            accuracyMeter: $accuracyMeter,
            reason: $reason,
        );
    }

    public static function suspicious(
        float $distanceMeter,
        float $accuracyMeter,
        string $reason,
    ): self {
        return new self(
            isValid: true,
            isSuspicious: true,
            distanceMeter: $distanceMeter,
            accuracyMeter: $accuracyMeter,
            reason: $reason,
        );
    }

    public static function invalid(
        float $distanceMeter,
        float $accuracyMeter,
        string $reason,
    ): self {
        return new self(
            isValid: false,
            isSuspicious: false,
            distanceMeter: $distanceMeter,
            accuracyMeter: $accuracyMeter,
            reason: $reason,
        );
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'is_suspicious' => $this->isSuspicious,
            'distance_meter' => $this->distanceMeter,
            'accuracy_meter' => $this->accuracyMeter,
            'reason' => $this->reason,
        ];
    }
}
