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
}
