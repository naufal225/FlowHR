<?php

namespace App\Services;

use App\Models\OfficeLocation;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OfficeLocationService
{
    public function getPaginated(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return OfficeLocation::query()
            ->withCount(['users', 'attendances'])
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

    public function getDetailPageData(OfficeLocation $officeLocation, int $perPage = 10): array
    {
        $officeLocation->loadCount(['users', 'attendances']);

        $assignedEmployees = $officeLocation->users()
            ->select([
                'id',
                'name',
                'email',
                'division_id',
                'office_location_id',
                'is_active',
                'created_at',
            ])
            ->with(['division:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'officeLocation' => $officeLocation,
            'assignedEmployees' => $assignedEmployees,
        ];
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

    public function delete(OfficeLocation $officeLocation): int
    {
        if ($officeLocation->attendances()->exists()) {
            throw new DomainException('Office location cannot be deleted because it already has attendance history.');
        }

        return DB::transaction(function () use ($officeLocation): int {
            $affectedUsers = User::query()
                ->where('office_location_id', $officeLocation->id)
                ->update(['office_location_id' => null]);

            $officeLocation->delete();

            return $affectedUsers;
        });
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
