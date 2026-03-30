<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveOfficeLocationTimezoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90', 'not_in:0'],
            'longitude' => ['required', 'numeric', 'between:-180,180', 'not_in:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required before timezone detection can run.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'latitude.not_in' => 'Latitude cannot remain at 0 for office timezone detection.',
            'longitude.required' => 'Longitude is required before timezone detection can run.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            'longitude.not_in' => 'Longitude cannot remain at 0 for office timezone detection.',
        ];
    }
}
