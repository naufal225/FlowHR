<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostSetting extends Model
{
    protected $table = 'cost_settings';

    protected $fillable = [
        'key',
        'name',
        'description',
        'value'
    ];

    protected $casts = [
        'value' => 'decimal:2'
    ];
}
