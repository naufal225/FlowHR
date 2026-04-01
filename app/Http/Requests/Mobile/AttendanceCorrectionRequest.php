<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'integer', 'exists:attendances,id'],
            'requested_check_in_time' => ['nullable', 'date', 'required_without:requested_check_out_time'],
            'requested_check_out_time' => ['nullable', 'date', 'required_without:requested_check_in_time'],
            'reason' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'in:pending,approved,rejected,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_id.required' => 'Log absensi wajib dipilih.',
            'attendance_id.exists' => 'Log absensi tidak ditemukan.',
            'requested_check_in_time.required_without' => 'Minimal salah satu waktu koreksi harus diisi.',
            'requested_check_out_time.required_without' => 'Minimal salah satu waktu koreksi harus diisi.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ];
    }
}
