<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageUserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:dns', 'max:255', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(Roles::values())],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'office_location_id' => ['nullable', 'exists:office_locations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'This email address is already taken.',
            'roles.required' => 'At least one role is required.',
            'roles.*.in' => 'Invalid role selected.',
            'office_location_id.exists' => 'Selected office location is invalid.',
        ];
    }
}
