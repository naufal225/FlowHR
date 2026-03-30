<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveOfficeLocationTimezoneRequest;
use App\Http\Requests\StoreOfficeLocationRequest;
use App\Http\Requests\UpdateOfficeLocationRequest;
use App\Models\OfficeLocation;
use App\Services\OfficeLocationService;
use App\Services\OfficeLocationTimezoneResolverService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    public function __construct(
        private readonly OfficeLocationService $officeLocationService,
        private readonly OfficeLocationTimezoneResolverService $officeLocationTimezoneResolverService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $officeLocations = $this->officeLocationService->getPaginated($search);

        return view('super-admin.office-location.index', compact('officeLocations', 'search'));
    }

    public function create(): View
    {
        return view('admin.office-location.create', $this->formContext());
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
        return view('admin.office-location.update', array_merge($this->formContext(), compact('officeLocation')));
    }

    public function update(UpdateOfficeLocationRequest $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $this->officeLocationService->update($officeLocation, $request->validated());

        return redirect()
            ->route('super-admin.office-locations.index')
            ->with('success', 'Office location updated successfully.');
    }

    public function destroy(OfficeLocation $officeLocation): RedirectResponse
    {
        try {
            $affectedUsers = $this->officeLocationService->delete($officeLocation);
        } catch (DomainException $exception) {
            return redirect()
                ->route('super-admin.office-locations.index')
                ->with('error', $exception->getMessage());
        }

        $message = 'Office location deleted successfully.';

        if ($affectedUsers > 0) {
            $message .= ' ' . $affectedUsers . ' assigned employee' . ($affectedUsers === 1 ? ' was' : 's were') . ' unassigned from this office.';
        }

        return redirect()
            ->route('super-admin.office-locations.index')
            ->with('success', $message);
    }

    public function resolveTimezone(ResolveOfficeLocationTimezoneRequest $request): JsonResponse
    {
        $result = $this->officeLocationTimezoneResolverService->resolve(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        return response()->json([
            'success' => $result->resolved,
            'message' => $result->message,
            'data' => $result->toArray(),
        ]);
    }

    private function formContext(): array
    {
        return [
            'layout' => 'components.super-admin.layout.layout-super-admin',
            'routePrefix' => 'super-admin',
        ];
    }
}
