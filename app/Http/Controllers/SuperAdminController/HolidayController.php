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
        $holidays = Holiday::orderBy('holiday_date', 'desc')->paginate(10);

        if ($request->filled('from_date')) {
            $fromDate = Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta');
            $holidays->where('holiday_date', '>=', $fromDate);
        }

        if ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta');
            $holidays->where('holiday_date', '<=', $toDate);
        }

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
            'holiday_date' => 'required|date|unique:holidays,holiday_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Holiday::create($request->all());

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
            'holiday_date' => 'required|date|unique:holidays,holiday_date,' . $holiday->id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $holiday->update($request->all());

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
