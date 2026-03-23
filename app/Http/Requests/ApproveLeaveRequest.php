<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasActiveRole(Roles::Manager->value) || auth()->user()->hasActiveRole(Roles::Approver->value);
    }

    public function rules(): array
    {
        return [
            'status_1' => 'nullable|string|in:approved,rejected',
            'status_2' => 'nullable|string|in:approved,rejected',
            'note_1' => 'nullable|string|min:3|max:100',
            'note_2' => 'nullable|string|min:3|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status_1.in' => 'Status 1 hanya boleh berisi: approved atau rejected.',
            'status_2.in' => 'Status 2 hanya boleh berisi: approved atau rejected.',
            'note_1.string' => 'Catatan 1 harus berupa teks.',
            'note_1.min' => 'Catatan 1 minimal harus berisi 3 karakter.',
            'note_1.max' => 'Catatan 1 maksimal hanya boleh 100 karakter.',
            'note_2.string' => 'Catatan 2 harus berupa teks.',
            'note_2.min' => 'Catatan 2 minimal harus berisi 3 karakter.',
            'note_2.max' => 'Catatan 2 maksimal hanya boleh 100 karakter.',
        ];
    }
}
