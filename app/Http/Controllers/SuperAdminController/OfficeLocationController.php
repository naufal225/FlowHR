<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficeLocationRequest;
use App\Http\Requests\UpdateOfficeLocationRequest;
use App\Models\OfficeLocation;
use App\Services\OfficeLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    public function __construct(private readonly OfficeLocationService $officeLocationService)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $officeLocations = $this->officeLocationService->getPaginated($search);

        return view('super-admin.office-location.index', compact('officeLocations', 'search'));
    }

    public function create(): View
    {
        return view('super-admin.office-location.create');
    }

    public function store(StoreOfficeLocationRequest $request): RedirectResponse
    {
        $this->officeLocationService->create($request->validated());

        return redirect()
            ->route('super-admin.office-locations.index')
            ->with('success', 'Office location created successfully.');
    }

    public function edit(OfficeLocation $officeLocation): View
    {
        return view('super-admin.office-location.update', compact('officeLocation'));
    }

    public function update(UpdateOfficeLocationRequest $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $this->officeLocationService->update($officeLocation, $request->validated());

        return redirect()
            ->route('super-admin.office-locations.index')
            ->with('success', 'Office location updated successfully.');
    }
}
