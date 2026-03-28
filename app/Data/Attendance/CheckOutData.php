<?php

namespace App\Data\Attendance;

use Illuminate\Http\Request;

class CheckOutData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $qrToken,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?float $accuracyMeter,
        public readonly ?string $deviceInfo = null,
        public readonly ?string $ipAddress = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            qrToken: trim((string) $data['qr_token']),
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            accuracyMeter: isset($data['accuracy_meter']) && $data['accuracy_meter'] !== ''
                ? (float) $data['accuracy_meter']
                : null,
            deviceInfo: isset($data['device_info']) ? (string) $data['device_info'] : null,
            ipAddress: isset($data['ip_address']) ? (string) $data['ip_address'] : null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            qrToken: trim((string) $request->input('qr_token')),
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude'),
            accuracyMeter: $request->filled('accuracy_meter')
                ? (float) $request->input('accuracy_meter')
                : null,
            deviceInfo: $request->userAgent(),
            ipAddress: $request->ip(),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'qr_token' => $this->qrToken,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy_meter' => $this->accuracyMeter,
            'device_info' => $this->deviceInfo,
            'ip_address' => $this->ipAddress,
        ];
    }
}
