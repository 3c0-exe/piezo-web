<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'description',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Convenience method to write an event log entry.
     *
     * Usage: EventLog::record('tracking_started', 'Tracking started.', ['student_id' => 1]);
     */
    public static function record(string $type, string $description, array $meta = []): static
    {
        return static::create([
            'event_type'  => $type,
            'description' => $description,
            'meta'        => empty($meta) ? null : $meta,
            'occurred_at' => now(),
        ]);
    }
}
