<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargingSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_name',
        'student_email',
        'started_at',
        'ended_at',
        'total_steps',
        'peak_watts',
        'peak_voltage',
        'battery_start',
        'battery_end',
        'flagged_overtime',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'peak_watts'       => 'float',
        'peak_voltage'     => 'float',
        'battery_start'    => 'float',
        'battery_end'      => 'float',
        'flagged_overtime' => 'boolean',
        'total_steps'      => 'integer',
    ];

    // ── Is the session still active? ─────────────────────────────────
    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    // ── Duration in seconds ───────────────────────────────────────────
    public function durationSeconds(): int
    {
        $end = $this->ended_at ?? now();
        return (int) $this->started_at->diffInSeconds($end);
    }

    // ── Human readable duration ───────────────────────────────────────
    public function durationFormatted(): string
    {
        $seconds = $this->durationSeconds();
        $minutes = intdiv($seconds, 60);
        $secs    = $seconds % 60;

        return $minutes > 0 ? "{$minutes}m {$secs}s" : "{$secs}s";
    }
}