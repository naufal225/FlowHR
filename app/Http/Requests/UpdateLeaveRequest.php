<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leave = $this->route('leave'); // dapet model dari route binding
        return Auth::check() && $leave && $leave->employee_id == Auth::id();
    }

    public function rules(): array
    {
        return [
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'reason' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'date_start.required' => 'Tanggal/Waktu Mulai harus diisi.',
            'date_end.after_or_equal' => 'Tanggal/Waktu Akhir harus setelah atau sama dengan Tanggal/Waktu Mulai.',
        ];
    }
}
