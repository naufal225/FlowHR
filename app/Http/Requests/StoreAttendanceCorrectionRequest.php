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

    public function rules()
    {
        return [
            'attendance_record_id' => 'required|integer|exists:attendance_records,id',
            'requested_check_in_time' => 'nullable|date',
            'requested_check_out_time' => 'nullable|date',
            'reason' => 'required|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'attendance_record_id.required' => 'Log absensi wajib dipilih.',
            'attendance_record_id.exists' => 'Log absensi tidak ditemukan.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ];
    }
}

