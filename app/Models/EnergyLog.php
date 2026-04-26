<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_email',
        'student_name',
        'steps',
        'watts',
        'voltage',
        'battery_percentage',
        'battery_health',
        'is_charging',
        'charging_source',
        'logged_at',
    ];

    protected $casts = [
        'logged_at'          => 'datetime',
        'watts'              => 'float',
        'voltage'            => 'float',
        'battery_percentage' => 'float',
        'steps'              => 'integer',
        'is_charging'        => 'boolean',
    ];
}