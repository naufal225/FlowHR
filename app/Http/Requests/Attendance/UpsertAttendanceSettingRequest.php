<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpsertAttendanceSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'work_start_time' => $this->mergeTimeInput('work_start_time'),
            'work_end_time' => $this->mergeTimeInput('work_end_time'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'office_location_id' => ['required', 'integer', 'exists:office_locations,id'],
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'qr_rotation_seconds' => ['required', 'integer', 'min:15', 'max:3600'],
            'min_location_accuracy_meter' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function mergeTimeInput(string $field): mixed
    {
        $hourField = $field . '_hour';
        $minuteField = $field . '_minute';

        if (!$this->exists($hourField) && !$this->exists($minuteField)) {
            return $this->input($field);
        }

        $hour = (string) $this->input($hourField, '');
        $minute = (string) $this->input($minuteField, '');

        $validHours = array_map(
            static fn (int $value): string => str_pad((string) $value, 2, '0', STR_PAD_LEFT),
            range(0, 23)
        );
        $validMinutes = array_map(
            static fn (int $value): string => str_pad((string) $value, 2, '0', STR_PAD_LEFT),
            range(0, 55, 5)
        );

        if (!in_array($hour, $validHours, true) || !in_array($minute, $validMinutes, true)) {
            return null;
        }

        return $hour . ':' . $minute;
    }
}
