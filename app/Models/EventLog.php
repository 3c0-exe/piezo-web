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
        'occurred_at' => 'datetime',
        'meta'        => 'array',
    ];

    /**
     * Convenience method used throughout the app to write an event.
     *
     * Usage:
     *   EventLog::record('tracking_started', 'Tracking was turned on.');
     *   EventLog::record('data_received', 'Sensor data received from ESP32.', ['watts' => 1.2]);
     */
    public static function record(string $eventType, string $description, ?array $meta = null): self
    {
        return static::create([
            'event_type'  => $eventType,
            'description' => $description,
            'meta'        => $meta,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Human-readable label for each event type.
     */
    public function eventTypeLabel(): string
    {
        return match ($this->event_type) {
            'tracking_started'  => 'Tracking Started',
            'tracking_stopped'  => 'Tracking Stopped',
            'student_assigned'  => 'Student Assigned',
            'esp32_connected'   => 'ESP32 Connected',
            'session_completed' => 'Session Completed',
            'session_overtime'  => 'Session Overtime',
            'data_received'     => 'Data Received',
            default             => ucwords(str_replace('_', ' ', $this->event_type)),
        };
    }

    /**
     * Tailwind color classes per event type for the badge.
     */
    public function eventTypeBadgeClass(): string
    {
        return match ($this->event_type) {
            'tracking_started'  => 'bg-green-500/10 text-green-400 border-green-500/20',
            'tracking_stopped'  => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
            'student_assigned'  => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            'esp32_connected'   => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
            'session_completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'session_overtime'  => 'bg-red-500/10 text-red-400 border-red-500/20',
            'data_received'     => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            default             => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        };
    }
}
