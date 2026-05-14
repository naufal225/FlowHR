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
        return Auth::check() && (
            Auth::user()->hasRole(Roles::Approver->value)
            || Auth::user()->hasRole(Roles::Manager->value)
        );
    }

    public function rules(): array
    {
        $user = Auth::user();

        $status1Rule = 'nullable|string|in:approved,rejected';
        $status2Rule = 'nullable|string|in:approved,rejected';

        if ($user->hasRole(Roles::Approver->value)) {
            $status1Rule = 'required|string|in:approved,rejected';
        }

        if ($user->hasRole(Roles::Manager->value)) {
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
