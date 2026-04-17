<?php

namespace App\Http\Requests\Attendance;

use App\Models\OfficeLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceQrDisplaySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'office_location_id' => ['required', 'integer', 'exists:office_locations,id'],
            'name' => ['required', 'string', 'max:120'],
            'ttl_days' => ['nullable', 'integer', 'min:1', 'max:365', 'required_without:expires_at'],
            'expires_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'required_without:ttl_days'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $expiresAtRaw = trim((string) $this->input('expires_at', ''));
            if ($expiresAtRaw === '') {
                return;
            }

            $office = OfficeLocation::query()->find((int) $this->input('office_location_id'));
            $timezone = (string) ($office?->timezone ?: config('app.timezone', 'Asia/Jakarta'));

            try {
                $expiresAt = Carbon::createFromFormat('Y-m-d\TH:i', $expiresAtRaw, $timezone);
            } catch (\Throwable) {
                return;
            }

            if (! $expiresAt instanceof Carbon) {
                return;
            }

            $now = now($timezone);
            if ($expiresAt->lte($now)) {
                $validator->errors()->add('expires_at', 'Waktu expired harus lebih besar dari waktu sekarang.');
            }

            if ($expiresAt->gt($now->copy()->addDays(365))) {
                $validator->errors()->add('expires_at', 'Waktu expired maksimal 365 hari dari sekarang.');
            }
        });
    }
}
