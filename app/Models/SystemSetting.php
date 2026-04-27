<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'is_tracking_on',
        'active_student_name',
        'active_student_email',
        'tracking_started_at',
        'device_total_steps',
    ];

    protected $casts = [
        'is_tracking_on'      => 'boolean',
        'tracking_started_at' => 'datetime',
        'device_total_steps'  => 'integer',
    ];

    public static function current(): static
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'is_tracking_on'       => false,
                'active_student_name'  => null,
                'active_student_email' => null,
                'tracking_started_at'  => null,
            ]
        );
    }
}