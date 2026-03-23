<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'holidays';

    protected $fillable = [
        'holiday_date',
        'name'
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];
}
