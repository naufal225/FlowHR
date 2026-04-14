<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceQrDisplaySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'office_location_id' => ['required', 'integer', 'exists:office_locations,id'],
            'name' => ['required', 'string', 'max:120'],
            'ttl_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
