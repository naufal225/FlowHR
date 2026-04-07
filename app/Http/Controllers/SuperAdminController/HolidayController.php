<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Holiday::query()
            ->orderByDesc('start_from');

        if ($request->filled('from_date')) {
            $fromDate = Carbon::parse($request->from_date)->toDateString();
            $query->where(function ($builder) use ($fromDate): void {
                $builder
                    ->whereDate('end_at', '>=', $fromDate)
                    ->orWhere(function ($subQuery) use ($fromDate): void {
                        $subQuery
                            ->whereNull('end_at')
                            ->whereDate('start_from', '>=', $fromDate);
                    });
            });
        }

        if ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date)->toDateString();
            $query->whereDate('start_from', '<=', $toDate);
        }

        $holidays = $query->paginate(10)->withQueryString();

        return view('super-admin.holiday.index', compact('holidays'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('super-admin.holiday.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_from' => 'required|date|unique:holidays,start_from',
            'end_at' => 'nullable|date|after_or_equal:start_from',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Holiday::create($validator->validated());

        return redirect()->route('super-admin.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Holiday $holiday)
    {
        return view('super-admin.holiday.show', compact('holiday'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Holiday $holiday)
    {
        return view('super-admin.holiday.update', compact('holiday'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Holiday $holiday)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_from' => 'required|date|unique:holidays,start_from,' . $holiday->id,
            'end_at' => 'nullable|date|after_or_equal:start_from',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $holiday->update($validator->validated());

        return redirect()->route('super-admin.holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->route('super-admin.holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}
