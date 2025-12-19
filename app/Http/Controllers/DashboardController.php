<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntrusionLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function gotoDashboard(Request $request)
    {
        $window = $request->get('window', 'all');
        $selectedWindowLabel = $this->getWindowLabel($window);
        $dateRange = $this->getDateRange($window);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Exclude blocked logs from dashboard counts
        $logs = IntrusionLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('status')
            ->latest()
            ->get();

        $totalLogs = $logs->count();
        $attackCount = $logs->where('risk_level', 'attack')->count();
        $benignCount = $logs->where('risk_level', 'benign')->count();
        $attackRate = $totalLogs > 0 ? ($attackCount / $totalLogs) * 100 : 0;

        $periodA_label = $this->getPeriodLabel($window, 'A');
        $attackCountA = $attackCount;
        $periodB_label = $this->getPeriodLabel($window, 'B');
        $benignCountB = $benignCount;

        $chartData = $this->getChartData($window, $startDate, $endDate);
        $attackByType = $this->getAttacksByType($startDate, $endDate);
        $topAttackedIPs = $this->getTopAttackedIPs($startDate, $endDate);
        $riskLevelDistribution = $this->getRiskLevelDistribution($startDate, $endDate);

        return view('admin.dashboard', compact(
            'logs',
            'totalLogs',
            'attackCount',
            'benignCount',
            'attackRate',
            'selectedWindowLabel',
            'window',
            'periodA_label',
            'attackCountA',
            'periodB_label',
            'benignCountB',
            'chartData',
            'attackByType',
            'topAttackedIPs',
            'riskLevelDistribution'
        ));
    }

    private function getWindowLabel($window)
    {
        return match($window) {
            '24h' => 'Last 24 Hours',
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
            'all' => 'All Time',
            default => 'All Time',
        };
    }

    private function getDateRange($window)
    {
        $end = Carbon::now();
        $start = match($window) {
            '24h' => $end->copy()->subHours(24),
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            'all' => IntrusionLog::oldest()->value('created_at') ? Carbon::parse(IntrusionLog::oldest()->value('created_at')) : $end->copy()->subYears(5),
            default => IntrusionLog::oldest()->value('created_at') ? Carbon::parse(IntrusionLog::oldest()->value('created_at')) : $end->copy()->subYears(5),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function getPeriodLabel($window, $period)
    {
        if ($period === 'A') {
            return match($window) {
                '24h' => 'Today',
                '7d' => 'This Week',
                '30d' => 'This Month',
                'all' => 'All Time',
                default => 'All Time',
            };
        }
        return match($window) {
            '24h' => 'Last Hour',
            '7d' => 'Last Week',
            '30d' => 'Last Month',
            'all' => 'Historical',
            default => 'Historical',
        };
    }

    private function getChartData($window, $startDate, $endDate)
    {
        $labels = [];
        $benignSeries = [];
        $attackSeries = [];

        if ($window === '24h') {
            for ($i = 0; $i < 24; $i++) {
                $hourStart = $startDate->copy()->addHours($i);
                $hourEnd = $hourStart->copy()->addHour();
                $labels[] = $hourStart->format('H:00');
                $benignSeries[] = IntrusionLog::whereBetween('created_at', [$hourStart, $hourEnd])->whereNull('status')->where('risk_level', 'benign')->count();
                $attackSeries[] = IntrusionLog::whereBetween('created_at', [$hourStart, $hourEnd])->whereNull('status')->where('risk_level', 'attack')->count();
            }
        } elseif ($window === 'all') {
            $rows = IntrusionLog::whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('status')
                ->selectRaw("DATE(created_at) as date, SUM(case when risk_level = 'benign' then 1 else 0 end) as benign, SUM(case when risk_level = 'attack' then 1 else 0 end) as attacks")
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            foreach ($rows as $row) {
                $labels[] = Carbon::parse($row->date)->format('M d');
                $benignSeries[] = (int) $row->benign;
                $attackSeries[] = (int) $row->attacks;
            }
        } else {
            $days = $window === '7d' ? 7 : 30;
            for ($i = 0; $i < $days; $i++) {
                $dayStart = $startDate->copy()->startOfDay()->addDays($i);
                $dayEnd = $dayStart->copy()->endOfDay();
                $labels[] = $dayStart->format('M d');
                $benignSeries[] = IntrusionLog::whereBetween('created_at', [$dayStart, $dayEnd])->whereNull('status')->where('risk_level', 'benign')->count();
                $attackSeries[] = IntrusionLog::whereBetween('created_at', [$dayStart, $dayEnd])->whereNull('status')->where('risk_level', 'attack')->count();
            }
        }

        return [
            'labels' => $labels,
            'benign' => $benignSeries,
            'attacks' => $attackSeries,
        ];
    }

    private function getAttacksByType($startDate, $endDate)
    {
        $attacks = IntrusionLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('status')
            ->where('risk_level', 'attack')
            ->groupBy('attack_type')
            ->selectRaw('attack_type, count(*) as count')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        if ($attacks->isEmpty()) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        return [
            'labels' => $attacks->pluck('attack_type')->toArray(),
            'data' => $attacks->pluck('count')->toArray()
        ];
    }

    private function getTopAttackedIPs($startDate, $endDate)
    {
        $ips = IntrusionLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('status')
            ->where('risk_level', 'attack')
            ->groupBy('dst_ip')
            ->selectRaw('dst_ip, count(*) as count')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return $ips;
    }

    private function getRiskLevelDistribution($startDate, $endDate)
    {
        $distribution = IntrusionLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('status')
            ->groupBy('risk_level')
            ->selectRaw('risk_level, count(*) as count')
            ->get();

        if ($distribution->isEmpty()) {
            return ['labels' => ['none'], 'data' => [0]];
        }

        return [
            'labels' => $distribution->pluck('risk_level')->toArray(),
            'data' => $distribution->pluck('count')->toArray()
        ];
    }

    public function refreshData(Request $request)
    {
        $window = $request->get('window', 'all');
        $selectedWindowLabel = $this->getWindowLabel($window);
        $dateRange = $this->getDateRange($window);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Exclude blocked logs from refreshed counts
        $logs = IntrusionLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('status')
            ->latest()
            ->get();

        $totalLogs = $logs->count();
        $attackCount = $logs->where('risk_level', 'attack')->count();
        $benignCount = $logs->where('risk_level', 'benign')->count();
        $attackRate = $totalLogs > 0 ? ($attackCount / $totalLogs) * 100 : 0;

        $chartData = $this->getChartData($window, $startDate, $endDate);
        $attackByType = $this->getAttacksByType($startDate, $endDate);
        $topAttackedIPs = $this->getTopAttackedIPs($startDate, $endDate)
            ->map(fn($ip) => ['dst_ip' => $ip->dst_ip, 'count' => $ip->count])
            ->values()
            ->toArray();
        $riskLevelDistribution = $this->getRiskLevelDistribution($startDate, $endDate);

        return response()->json([
            'selectedWindowLabel' => $selectedWindowLabel,
            'window' => $window,
            'totalLogs' => $totalLogs,
            'attackCount' => $attackCount,
            'benignCount' => $benignCount,
            'attackRate' => round($attackRate, 2),
            'chartData' => $chartData,
            'attackByType' => $attackByType,
            'topAttackedIPs' => $topAttackedIPs,
            'riskLevelDistribution' => $riskLevelDistribution,
        ]);
    }
}