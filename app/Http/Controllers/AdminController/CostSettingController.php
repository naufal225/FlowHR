<?php

namespace App\Http\Controllers\AdminController;

use App\Http\Controllers\Controller;
use App\Models\CostSetting;
use App\Models\FeatureSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CostSettingController extends Controller
{
    public function index()
    {
        $settings = CostSetting::all();
        $features = FeatureSetting::pluck('is_enabled', 'feature_name');
        return view('admin.cost-settings.index', compact('settings', 'features'));
    }

    public function edit(CostSetting $costSetting)
    {
        return view('admin.cost-settings.edit', compact('costSetting'));
    }

    public function update(Request $request, CostSetting $costSetting)
    {
        $rules = $costSetting->key === 'ANNUAL_LEAVE'
            ? ['value' => 'required|integer|min:0']
            : ['value' => 'required|numeric|min:0'];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $value = $costSetting->key === 'ANNUAL_LEAVE' ? (int) $request->value : $request->value;
        $costSetting->update(['value' => $value]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    public function updateMultiple(Request $request)
    {
        $settings = $request->except('_token');

        foreach ($settings as $key => $value) {
            $setting = CostSetting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $key === 'ANNUAL_LEAVE' ? (int) $value : $value]);
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    public function updateFeatures(Request $request)
    {
        $allowed = ['cuti', 'reimbursement', 'overtime', 'perjalanan_dinas'];
        foreach ($allowed as $key) {
            $active = $request->boolean($key);
            FeatureSetting::updateOrCreate(
                ['feature_name' => $key],
                ['is_enabled' => $active]
            );
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Feature settings updated successfully.');
    }
}
