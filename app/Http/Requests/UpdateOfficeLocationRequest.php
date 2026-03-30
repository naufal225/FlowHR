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
            'latitude' => ['required', 'numeric', 'between:-90,90', 'not_in:0'],
            'longitude' => ['required', 'numeric', 'between:-180,180', 'not_in:0'],
            'radius_meter' => ['required', 'integer', 'min:1', 'max:1000'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.not_in' => 'Choose the office point on the map before saving.',
            'longitude.not_in' => 'Choose the office point on the map before saving.',
            'radius_meter.max' => 'Radius above 1,000 meters is too wide for a standard office attendance area.',
            'timezone.required' => 'Timezone is required. Select a location or enter a valid timezone manually.',
            'timezone.timezone' => 'Timezone must be a valid IANA timezone, for example Asia/Jakarta.',
        ];
    }
}

