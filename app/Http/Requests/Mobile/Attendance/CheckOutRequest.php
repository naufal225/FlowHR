<?php

namespace App\Http\Requests\Mobile\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'qr_token' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meter' => ['nullable', 'numeric', 'min:0'],
            'device_info' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'qr_token' => 'QR token',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'accuracy_meter' => 'akurasi lokasi',
            'device_info' => 'informasi perangkat',
        ];
    }
}
