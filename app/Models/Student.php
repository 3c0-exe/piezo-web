<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'name',
        'student_id',
        'section',
        'year_level',
    ];

    public function energyLogs(): HasMany
    {
        return $this->hasMany(EnergyLog::class);
    }

    public function chargingSessions(): HasMany
    {
        return $this->hasMany(ChargingSession::class);
    }
}
