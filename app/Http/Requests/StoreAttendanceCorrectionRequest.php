<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'attendance_record_id' => ['required', 'integer', 'exists:attendances,id'],
            'requested_check_in_time' => ['nullable', 'date', 'required_without:requested_check_out_time'],
            'requested_check_out_time' => ['nullable', 'date', 'required_without:requested_check_in_time'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_record_id.required' => 'Log absensi wajib dipilih.',
            'attendance_record_id.exists' => 'Log absensi tidak ditemukan.',
            'requested_check_in_time.required_without' => 'Minimal salah satu waktu koreksi harus diisi.',
            'requested_check_out_time.required_without' => 'Minimal salah satu waktu koreksi harus diisi.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ];
    }
}
