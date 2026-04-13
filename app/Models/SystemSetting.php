<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'active_student_id',
        'is_tracking_on',
        'tracking_started_at',
    ];

    protected $casts = [
        'is_tracking_on'      => 'boolean',
        'tracking_started_at' => 'datetime',
    ];

    public function activeStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'active_student_id');
    }

    /**
     * Always returns the single singleton row (id = 1).
     */
    public static function current(): static
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'active_student_id'  => null,
                'is_tracking_on'     => false,
                'tracking_started_at'=> null,
            ]
        );
    }
}
