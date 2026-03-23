<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateOfficialTravelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $officialTravel = $this->route('officialTravel'); // dapet model dari route binding
        return Auth::check() && $officialTravel && $officialTravel->employee_id == Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer' => 'required|string|max:255',
            'date_start' => 'required|date|after_or_equal:today',
            'date_end' => 'required|date|after_or_equal:date_start',
        ];
    }

    public function messages(): array
    {
        return [
            'customer.required' => 'Customer harus diisi.',
            'date_start.required' => 'Tanggal mulai harus diisi.',
            'date_end.required' => 'Tanggal selesai harus diisi.',
            'date_end.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
