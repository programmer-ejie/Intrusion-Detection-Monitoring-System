<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntrusionLog extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'incident_id','src_ip','pppoe_name','dst_ip','src_port','dst_port','protocol',
        'flow_duration','flow_pkts_s','flow_bytes_s',
        'tot_fwd_pkts','tot_bwd_pkts','tot_fwd_bytes','tot_bwd_bytes',
        'fwd_pkt_len_mean','bwd_pkt_len_mean',
        'fwd_iat_mean','bwd_iat_mean',
        'risk_level','prob_attack','attack_type','status'
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(ThreatIncident::class, 'incident_id');
    }

}
