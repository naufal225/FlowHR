<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ApproveReimbursementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya Approver atau Manager yang aktif yang boleh approve
        return Auth::check() && (
            Auth::user()->hasActiveRole(Roles::Approver->value)
            || Auth::user()->hasActiveRole(Roles::Manager->value)
        );
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $activeRole = session('active_role');

        $status1Rule = 'nullable|string|in:approved,rejected';
        $status2Rule = 'nullable|string|in:approved,rejected';

        if ($activeRole === Roles::Approver->value) {
            $status1Rule = 'required|string|in:approved,rejected';
        }

        if ($activeRole === Roles::Manager->value) {
            $status2Rule = 'required|string|in:approved,rejected';
        }

        return [
            'status_1' => $status1Rule,
            'status_2' => $status2Rule,
            'note_1' => 'nullable|string|min:3|max:100',
            'note_2' => 'nullable|string|min:3|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status_1.required' => 'Status wajib diisi.',
            'status_1.in' => 'Status hanya boleh approved/rejected.',
            'status_2.required' => 'Status wajib diisi.',
            'status_2.in' => 'Status hanya boleh approved/rejected.',
            'note_1.min' => 'Catatan minimal 3 karakter.',
            'note_1.max' => 'Catatan maksimal 100 karakter.',
            'note_2.min' => 'Catatan minimal 3 karakter.',
            'note_2.max' => 'Catatan maksimal 100 karakter.',
        ];
    }

}
