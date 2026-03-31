<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'reviewer_note' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Aksi review wajib dipilih.',
            'action.in' => 'Aksi review tidak valid.',
            'reviewer_note.required_if' => 'Catatan reviewer wajib diisi saat menolak koreksi.',
        ];
    }
}
