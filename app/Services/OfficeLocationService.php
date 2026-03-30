<?php

namespace App\Services;

use App\Models\OfficeLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OfficeLocationService
{
    public function getPaginated(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return OfficeLocation::query()
            ->withCount('users')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $validated): OfficeLocation
    {
        return DB::transaction(function () use ($validated): OfficeLocation {
            return OfficeLocation::query()->create($this->normalizePayload($validated));
        });
    }

    public function update(OfficeLocation $officeLocation, array $validated): OfficeLocation
    {
        DB::transaction(function () use ($officeLocation, $validated): void {
            $officeLocation->fill($this->normalizePayload($validated));

            if ($officeLocation->isDirty()) {
                $officeLocation->save();
            }
        });

        return $officeLocation->refresh();
    }

    private function normalizePayload(array $validated): array
    {
        return [
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'address' => filled($validated['address'] ?? null) ? trim((string) $validated['address']) : null,
            'latitude' => round((float) $validated['latitude'], 7),
            'longitude' => round((float) $validated['longitude'], 7),
            'radius_meter' => (int) $validated['radius_meter'],
            'timezone' => trim($validated['timezone']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
