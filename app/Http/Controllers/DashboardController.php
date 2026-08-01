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

        $metrics = $this->getDashboardMetrics($window, $startDate, $endDate);

        // The dashboard view does not render the full raw logs table, so avoid
        // loading the entire dataset here on every page hit.
        $logs = collect();

        return view('admin.dashboard', [
            'logs' => $logs,
            'selectedWindowLabel' => $selectedWindowLabel,
            'window' => $window,
            'periodA_label' => $this->getPeriodLabel($window, 'A'),
            'attackCountA' => $metrics['attackCount'],
            'periodB_label' => $this->getPeriodLabel($window, 'B'),
            'benignCountB' => $metrics['benignCount'],
            'chartData' => $metrics['chartData'],
            'attackByType' => $metrics['attackByType'],
            'topAttackedIPs' => $metrics['topAttackedIPs'],
            'riskLevelDistribution' => $metrics['riskLevelDistribution'],
            'totalLogs' => $metrics['totalLogs'],
            'attackCount' => $metrics['attackCount'],
            'benignCount' => $metrics['benignCount'],
            'attackRate' => $metrics['attackRate'],
        ]);
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
            'all' => $this->getOldestCreatedAt(),
            default => $this->getOldestCreatedAt(),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function getOldestCreatedAt(): Carbon
    {
        $oldest = IntrusionLog::query()->min('created_at');

        return $oldest ? Carbon::parse($oldest) : Carbon::now()->subYears(5);
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
            $rows = IntrusionLog::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('status')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as bucket, risk_level, COUNT(*) as count")
                ->groupByRaw('bucket, risk_level')
                ->get();

            $bucketMap = [];
            foreach ($rows as $row) {
                $bucketMap[$row->bucket][$row->risk_level] = (int) $row->count;
            }

            for ($i = 0; $i < 24; $i++) {
                $hourStart = $startDate->copy()->addHours($i);
                $bucket = $hourStart->format('Y-m-d H:00:00');
                $labels[] = $hourStart->format('H:00');
                $benignSeries[] = $bucketMap[$bucket]['benign'] ?? 0;
                $attackSeries[] = $bucketMap[$bucket]['attack'] ?? 0;
            }
        } elseif ($window === 'all') {
            $rows = IntrusionLog::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('status')
                ->selectRaw("DATE(created_at) as bucket, risk_level, COUNT(*) as count")
                ->groupByRaw('bucket, risk_level')
                ->orderBy('bucket')
                ->get();

            $bucketMap = [];
            foreach ($rows as $row) {
                $bucketMap[$row->bucket][$row->risk_level] = (int) $row->count;
            }

            foreach (array_keys($bucketMap) as $bucket) {
                $labels[] = Carbon::parse($bucket)->format('M d');
                $benignSeries[] = $bucketMap[$bucket]['benign'] ?? 0;
                $attackSeries[] = $bucketMap[$bucket]['attack'] ?? 0;
            }
        } else {
            $days = $window === '7d' ? 7 : 30;
            $rows = IntrusionLog::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('status')
                ->selectRaw("DATE(created_at) as bucket, risk_level, COUNT(*) as count")
                ->groupByRaw('bucket, risk_level')
                ->orderBy('bucket')
                ->get();

            $bucketMap = [];
            foreach ($rows as $row) {
                $bucketMap[$row->bucket][$row->risk_level] = (int) $row->count;
            }

            for ($i = 0; $i < $days; $i++) {
                $day = $startDate->copy()->startOfDay()->addDays($i);
                $bucket = $day->format('Y-m-d');
                $labels[] = $day->format('M d');
                $benignSeries[] = $bucketMap[$bucket]['benign'] ?? 0;
                $attackSeries[] = $bucketMap[$bucket]['attack'] ?? 0;
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

        $metrics = $this->getDashboardMetrics($window, $startDate, $endDate);

        return response()->json([
            'selectedWindowLabel' => $selectedWindowLabel,
            'window' => $window,
            'totalLogs' => $metrics['totalLogs'],
            'attackCount' => $metrics['attackCount'],
            'benignCount' => $metrics['benignCount'],
            'attackRate' => round($metrics['attackRate'], 2),
            'chartData' => $metrics['chartData'],
            'attackByType' => $metrics['attackByType'],
            'topAttackedIPs' => $metrics['topAttackedIPs'],
            'riskLevelDistribution' => $metrics['riskLevelDistribution'],
        ]);
    }

    private function getDashboardMetrics($window, Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = IntrusionLog::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('status');

        $totalLogs = (clone $baseQuery)->count();
        $attackCount = (clone $baseQuery)->where('risk_level', 'attack')->count();
        $benignCount = (clone $baseQuery)->where('risk_level', 'benign')->count();
        $attackRate = $totalLogs > 0 ? ($attackCount / $totalLogs) * 100 : 0;

        return [
            'totalLogs' => $totalLogs,
            'attackCount' => $attackCount,
            'benignCount' => $benignCount,
            'attackRate' => $attackRate,
            'chartData' => $this->getChartData($window, $startDate, $endDate),
            'attackByType' => $this->getAttacksByType($startDate, $endDate),
            'topAttackedIPs' => $this->getTopAttackedIPs($startDate, $endDate)
                ->map(fn ($ip) => ['dst_ip' => $ip->dst_ip, 'count' => $ip->count])
                ->values()
                ->toArray(),
            'riskLevelDistribution' => $this->getRiskLevelDistribution($startDate, $endDate),
        ];
    }
}
