<?php

namespace App\Http\Requests\Mobile\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
