<?php

namespace App\Data\Attendance;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceHistoryFilterData
{
    public function __construct(
        public readonly ?Carbon $startDate = null,
        public readonly ?Carbon $endDate = null,
        public readonly ?string $recordStatus = null,
        public readonly ?string $checkInStatus = null,
        public readonly ?string $checkOutStatus = null,
        public readonly ?int $userId = null,
        public readonly ?int $officeLocationId = null,
        public readonly ?bool $isSuspicious = null,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'work_date',
        public readonly string $sortDirection = 'desc',
    ) {}

    public static function fromArray(array $data): self
    {
        $sortDirection = strtolower((string) ($data['sort_direction'] ?? 'desc'));
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        $perPage = (int) ($data['per_page'] ?? 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        return new self(
            startDate: !empty($data['start_date']) ? Carbon::parse($data['start_date'])->startOfDay() : null,
            endDate: !empty($data['end_date']) ? Carbon::parse($data['end_date'])->endOfDay() : null,
            recordStatus: isset($data['record_status']) && $data['record_status'] !== '' ? (string) $data['record_status'] : null,
            checkInStatus: isset($data['check_in_status']) && $data['check_in_status'] !== '' ? (string) $data['check_in_status'] : null,
            checkOutStatus: isset($data['check_out_status']) && $data['check_out_status'] !== '' ? (string) $data['check_out_status'] : null,
            userId: isset($data['user_id']) && $data['user_id'] !== '' ? (int) $data['user_id'] : null,
            officeLocationId: isset($data['office_location_id']) && $data['office_location_id'] !== '' ? (int) $data['office_location_id'] : null,
            isSuspicious: array_key_exists('is_suspicious', $data) && $data['is_suspicious'] !== ''
                ? filter_var($data['is_suspicious'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
            perPage: $perPage,
            sortBy: (string) ($data['sort_by'] ?? 'work_date'),
            sortDirection: $sortDirection,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate?->toDateTimeString(),
            'end_date' => $this->endDate?->toDateTimeString(),
            'record_status' => $this->recordStatus,
            'check_in_status' => $this->checkInStatus,
            'check_out_status' => $this->checkOutStatus,
            'user_id' => $this->userId,
            'office_location_id' => $this->officeLocationId,
            'is_suspicious' => $this->isSuspicious,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];
    }

    public function hasDateRange(): bool
    {
        return $this->startDate !== null || $this->endDate !== null;
    }
}
