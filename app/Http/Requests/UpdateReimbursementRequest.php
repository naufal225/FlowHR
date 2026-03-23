<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateReimbursementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $reimbursement = $this->route('reimbursement'); // dapet model dari route binding
        return Auth::check() && $reimbursement && $reimbursement->employee_id == Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer' => 'required|string',
            'total' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reimbursement_type_id' => 'required|exists:reimbursement_types,id',
            'invoice_path' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'remove_invoice_path' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'customer.required' => 'Customer harus dipilih.',
            'total.required' => 'Total harus diisi.',
            'total.numeric' => 'Total harus berupa angka.',
            'total.min' => 'Total tidak boleh kurang dari 0.',
            'date.required' => 'Tanggal harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'invoice_path.mimes' => 'File harus berupa jpg, jpeg, png, atau pdf.',
            'invoice_path.max' => 'Ukuran file maksimal 2MB.',
            'reimbursement_type_id.required' => 'Tipe reimbursement wajib dipilih.',
            'reimbursement_type_id.exists' => 'Tipe reimbursement tidak valid.',
        ];
    }
}
