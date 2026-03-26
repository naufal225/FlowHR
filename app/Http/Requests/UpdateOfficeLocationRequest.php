<?php

namespace App\Http\Requests;

use App\Models\OfficeLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        /** @var OfficeLocation|string|int|null $officeLocation */
        $officeLocation = $this->route('office_location');
        $officeLocationId = $officeLocation instanceof OfficeLocation ? $officeLocation->id : $officeLocation;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('office_locations', 'code')->ignore($officeLocationId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
