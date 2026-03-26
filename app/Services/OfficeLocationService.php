<?php

namespace App\Services;

use App\Models\OfficeLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        return OfficeLocation::query()->create($this->normalizePayload($validated));
    }

    public function update(OfficeLocation $officeLocation, array $validated): OfficeLocation
    {
        $officeLocation->update($this->normalizePayload($validated));

        return $officeLocation->refresh();
    }

    private function normalizePayload(array $validated): array
    {
        return [
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'address' => isset($validated['address']) ? trim($validated['address']) : null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meter' => $validated['radius_meter'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
