<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'steps',
        'watts',
        'voltage',
        'battery_percentage',
        'battery_health',
        'logged_at',
    ];

    protected $casts = [
        'logged_at'          => 'datetime',
        'watts'              => 'float',
        'voltage'            => 'float',
        'battery_percentage' => 'float',
        'steps'              => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
