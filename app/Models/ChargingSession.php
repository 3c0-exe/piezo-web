<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargingSession extends Model
{
    protected $fillable = [
        'student_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'total_steps',
        'peak_watts',
        'battery_start',
        'battery_end',
        'capacity_added',
        'flagged_overtime',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'peak_watts'       => 'float',
        'battery_start'    => 'float',
        'battery_end'      => 'float',
        'capacity_added'   => 'float',
        'flagged_overtime' => 'boolean',
        'duration_seconds' => 'integer',
        'total_steps'      => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Returns a human-readable duration string e.g. "12m 34s"
     */
    public function durationFormatted(): string
    {
        $seconds = $this->duration_seconds ?? 0;
        $minutes = intdiv($seconds, 60);
        $secs    = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }

        return "{$secs}s";
    }
}
