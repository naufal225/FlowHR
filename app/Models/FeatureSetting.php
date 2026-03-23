<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureSetting extends Model
{
    protected $fillable = ['feature_name', 'is_enabled'];

    public static function isActive(string $feature): bool
    {
        return self::where('feature_name', $feature)->value('is_enabled') ?? false;
    }
}
