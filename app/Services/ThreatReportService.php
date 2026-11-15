<?php

namespace App\Services;

use App\Models\IntrusionLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ThreatReportService
{
    // Prefer subscriber identity; use source IP only when the collector has no identity.
    public const CLIENT = "COALESCE(NULLIF(TRIM(pppoe_name), ''), src_ip, 'Unknown')";

    public function query(Request $request): Builder
    {
        $request->validate([
            'window' => 'nullable|string|in:24h,7d,30d,all',
            'client' => 'nullable|string|max:255',
        ]);
        $query = IntrusionLog::query();
        $days = ['24h' => 1, '7d' => 7, '30d' => 30][$request->query('window', 'all')] ?? null;
        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }
        if ($request->filled('client')) {
            $query->whereRaw(self::CLIENT.' = ?', [$request->query('client')]);
        }

        return $query;
    }

    public function summary(Builder $query): array
    {
        $counts = (clone $query)->selectRaw("COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN risk_level = 'attack' THEN 1 ELSE 0 END), 0) AS attacks,
            COALESCE(SUM(CASE WHEN risk_level = 'attack' AND prob_attack > 0.8 THEN 1 ELSE 0 END), 0) AS high_risk,
            COALESCE(SUM(CASE WHEN actual_label = 'attack' AND risk_level = 'attack' THEN 1 ELSE 0 END), 0) AS tp,
            COALESCE(SUM(CASE WHEN actual_label = 'benign' AND risk_level = 'attack' THEN 1 ELSE 0 END), 0) AS fp,
            COALESCE(SUM(CASE WHEN actual_label = 'attack' AND risk_level = 'benign' THEN 1 ELSE 0 END), 0) AS fn,
            COALESCE(SUM(CASE WHEN actual_label = 'benign' AND risk_level = 'benign' THEN 1 ELSE 0 END), 0) AS tn,
            COALESCE(SUM(CASE WHEN actual_label IS NULL THEN 1 ELSE 0 END), 0) AS unreviewed")->first();

        $clients = (clone $query)->selectRaw(self::CLIENT." AS client, COUNT(*) AS events,
            SUM(CASE WHEN risk_level = 'attack' THEN 1 ELSE 0 END) AS attempts,
            SUM(CASE WHEN actual_label = 'benign' AND risk_level = 'attack' THEN 1 ELSE 0 END) AS fp,
            SUM(CASE WHEN actual_label = 'attack' AND risk_level = 'benign' THEN 1 ELSE 0 END) AS fn,
            MIN(created_at) AS first_seen, MAX(created_at) AS last_seen")
            ->groupBy('client')
            ->havingRaw("SUM(CASE WHEN risk_level = 'attack' OR actual_label = 'attack' THEN 1 ELSE 0 END) > 0")
            ->orderByDesc('attempts')->orderBy('client')->get();

        return compact('counts', 'clients');
    }

    public static function outcome(IntrusionLog $log): string
    {
        if (! $log->actual_label) {
            return 'Unreviewed';
        }

        return match ([$log->risk_level, $log->actual_label]) {
            ['attack', 'attack'] => 'True positive',
            ['attack', 'benign'] => 'False positive',
            ['benign', 'attack'] => 'False negative',
            ['benign', 'benign'] => 'True negative',
            default => 'Unknown prediction',
        };
    }
}
