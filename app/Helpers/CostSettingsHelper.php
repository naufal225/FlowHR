<?php

namespace App\Helpers;

use App\Models\CostSetting;

class CostSettingsHelper
{
    public static function get($key, $default = null)
    {
        $setting = CostSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
