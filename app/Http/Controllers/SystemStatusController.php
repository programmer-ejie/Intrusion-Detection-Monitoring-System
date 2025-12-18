<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntrusionLog;
use Carbon\Carbon;

class SystemStatusController extends Controller
{
    public function gotoStatus(Request $request)
    {
        $window = $request->query('window', '24h');

        $systemStats = $this->getSystemStats($window);
        $processingMetrics = $this->getProcessingMetrics();
        $healthIndicators = $this->getHealthIndicators();
        $activityTimeline = $this->getActivityTimeline();

        return view('admin.system-status', compact(
            'systemStats',
            'processingMetrics',
            'healthIndicators',
            'activityTimeline'
        ));
    }
    
        // Return only the alert fragment (used by AJAX polling)
    public function alertFragment(Request $request)
    {
        // Keep same filter logic as gotoStatus
        $window = $request->query('window', '24h');
        $systemStats = $this->getSystemStats($window);
        $healthIndicators = $this->getHealthIndicators();

        // Render the partial view and return HTML
        return view('admin.partials.system-status-alert', compact('healthIndicators'))->render();
    }

    private function getSystemStats($window = '24h')
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        // determine window start
        $startWindow = null;
        switch ($window) {
            case 'all':
                $startWindow = null;
                break;
            case '7d':
                $startWindow = $now->copy()->subDays(7);
                break;
            case '30d':
                $startWindow = $now->copy()->subDays(30);
                break;
            case '24h':
            default:
                $startWindow = $now->copy()->subDay();
                break;
        }

        $baseQuery = IntrusionLog::query();
        if ($startWindow) {
            $baseQuery = $baseQuery->whereBetween('created_at', [$startWindow, $now]);
        }

        $totalLogs = (clone $baseQuery)->count();
        $logsToday = IntrusionLog::whereBetween('created_at', [$startOfDay, $now])->count();
        $logsThisWeek = IntrusionLog::whereBetween('created_at', [$startOfWeek, $now])->count();
        $logsThisMonth = IntrusionLog::whereBetween('created_at', [$startOfMonth, $now])->count();

        // first log for 'all' or startWindow as informational
        $firstLog = null;
        if ($startWindow === null) {
            $firstLog = IntrusionLog::oldest()->first();
        }

        // compute uptime in days as a float with 2 decimal precision
        // use signed difference so we can detect clock skew (first log in future)
        $systemUptime = null;
        $uptimeWarning = false;
        $earliest = $firstLog ?? ($startWindow ?: IntrusionLog::oldest()->first());
        if ($earliest) {
            $earliestTs = $earliest->created_at ?? $startWindow;
            $seconds = $now->getTimestamp() - ($earliestTs instanceof Carbon ? $earliestTs->getTimestamp() : $earliestTs->getTimestamp());
            $days = $seconds / 86400;
            if ($days < 0) {
                $uptimeWarning = true;
                $systemUptime = round($days, 2);
            } else {
                $systemUptime = round($days, 2);
            }
        }

        $attacksToday = IntrusionLog::where('risk_level', 'attack')
            ->whereBetween('created_at', [$startOfDay, $now])
            ->count();

        $benignToday = IntrusionLog::where('risk_level', 'benign')
            ->whereBetween('created_at', [$startOfDay, $now])
            ->count();

        $totalAttacks = IntrusionLog::where('risk_level', 'attack')->count();
        $totalBenign = IntrusionLog::where('risk_level', 'benign')->count();

        return [
            'total_logs' => $totalLogs,
            'logs_today' => $logsToday,
            'logs_this_week' => $logsThisWeek,
            'logs_this_month' => $logsThisMonth,
            'system_uptime' => $systemUptime,
            'uptime_warning' => $uptimeWarning,
            'attacks_today' => $attacksToday,
            'benign_today' => $benignToday,
            'total_attacks' => $totalAttacks,
            'total_benign' => $totalBenign,
            'first_log_date' => $firstLog ? $firstLog->created_at->format('M d, Y H:i') : ($startWindow ? $startWindow->format('M d, Y H:i') : 'N/A'),
            'last_log_date' => IntrusionLog::latest()->first()?->created_at->format('M d, Y H:i') ?? 'N/A',
        ];
    }

    private function getProcessingMetrics()
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();
        $lastDay = $now->copy()->subDay();

        $logsLastHour = IntrusionLog::whereBetween('created_at', [$lastHour, $now])->count();
        $logsLastDay = IntrusionLog::whereBetween('created_at', [$lastDay, $now])->count();

        $avgRate = $logsLastDay > 0 ? round($logsLastDay / 24, 2) : 0;
        $currentRate = $logsLastHour > 0 ? round($logsLastHour, 2) : 0;

        $avgPacketsPerFlow = IntrusionLog::average('tot_fwd_pkts') ?? 0;
        $avgBytesPerFlow = IntrusionLog::average('flow_bytes_s') ?? 0;

        return [
            'logs_last_hour' => $logsLastHour,
            'logs_last_day' => $logsLastDay,
            'average_hourly_rate' => $avgRate,
            'current_hour_rate' => $currentRate,
            'avg_packets_per_flow' => round($avgPacketsPerFlow, 2),
            'avg_bytes_per_flow' => round($avgBytesPerFlow, 2),
        ];
    }

    private function getHealthIndicators()
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();

        $totalLogs = IntrusionLog::count();
        $attackLogs = IntrusionLog::where('risk_level', 'attack')->count();

        $attackRate = $totalLogs > 0 ? round(($attackLogs / $totalLogs) * 100, 2) : 0;
        $criticalAlerts = IntrusionLog::where('prob_attack', '>=', 0.8)->count();
        $highRiskAlerts = IntrusionLog::whereBetween('prob_attack', [0.5, 0.79])->count();

        $avgProbAttack = round(IntrusionLog::average('prob_attack') ?? 0, 4);

        // Health status based on metrics
        $status = 'Healthy';
        if ($criticalAlerts > 100) {
            $status = 'Critical';
        } elseif ($criticalAlerts > 50 || $attackRate > 30) {
            $status = 'Warning';
        } elseif ($criticalAlerts > 20 || $attackRate > 15) {
            $status = 'Caution';
        }

        return [
            'status' => $status,
            'attack_rate' => $attackRate,
            'critical_alerts' => $criticalAlerts,
            'high_risk_alerts' => $highRiskAlerts,
            'avg_prob_attack' => $avgProbAttack,
        ];
    }

    private function getActivityTimeline()
    {
        $now = Carbon::now();
        $timeline = [];

        // Last 24 hours by hour
        for ($i = 23; $i >= 0; $i--) {
            $hourStart = $now->copy()->subHours($i)->startOfHour();
            $hourEnd = $hourStart->copy()->addHour();

            $count = IntrusionLog::whereBetween('created_at', [$hourStart, $hourEnd])->count();
            $attacks = IntrusionLog::where('risk_level', 'attack')
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->count();

            $timeline[] = [
                'time' => $hourStart->format('H:00'),
                'total' => $count,
                'attacks' => $attacks,
            ];
        }

        return $timeline;
    }
}
