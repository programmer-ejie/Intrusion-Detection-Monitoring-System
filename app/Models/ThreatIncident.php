<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ThreatIncident extends Model
{
    protected $fillable = [
        'pppoe_name', 'attack_type', 'status', 'event_count',
        'first_detected_at', 'last_detected_at', 'blocked_at',
        'restored_at', 'firewall_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'blocked_at' => 'datetime',
            'restored_at' => 'datetime',
            'firewall_synced_at' => 'datetime',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntrusionLog::class, 'incident_id');
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(IntrusionLog::class, 'incident_id')->latestOfMany();
    }
}
