<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|string|int|null $user */
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : $user;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:dns', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(Roles::values())],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'office_location_id' => ['nullable', 'exists:office_locations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role is required.',
            'roles.*.in' => 'Invalid role selected.',
            'office_location_id.exists' => 'Selected office location is invalid.',
        ];
    }
}
