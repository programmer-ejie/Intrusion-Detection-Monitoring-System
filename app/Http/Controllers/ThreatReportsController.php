<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\IntrusionLog;
use Carbon\Carbon;

class ThreatReportsController extends Controller
{
    public function index(Request $request)
    {
        $window = $request->query('window', '24h');

        // apply basic time window filter
        $query = IntrusionLog::query();
        switch ($window) {
            case '24h':
                $query->where('created_at', '>=', Carbon::now()->subDay());
                break;
            case '7d':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
            case '30d':
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
                break;
            case 'all':
            default:
                break;
        }

        $total = (clone $query)->count();
        $attacks = (clone $query)->where('risk_level', '>=', 3)->count();
        $highRisk = (clone $query)->where('risk_level', '>=', 4)->count();

        $latest = (clone $query)->orderByDesc('created_at')->first();

        $rows = (clone $query)->orderByDesc('created_at')->limit(50)->get();

        return view('admin.threat-reports', [
            'window' => $window,
            'total' => $total,
            'attacks' => $attacks,
            'highRisk' => $highRisk,
            'latest' => $latest,
            'rows' => $rows,
        ]);
    }

    public function data(Request $request)
    {
        $q = $request->query('q', '');
        $window = $request->query('window', '24h');

        $query = IntrusionLog::query();
        switch ($window) {
            case '24h':
                $query->where('created_at', '>=', Carbon::now()->subDay());
                break;
            case '7d':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
            case '30d':
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
                break;
            case 'all':
            default:
                break;
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('src_ip', 'like', "%{$q}%")
                    ->orWhere('dst_ip', 'like', "%{$q}%")
                    ->orWhere('protocol', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            });
        }

        $rows = $query->orderByDesc('created_at')->limit(200)->get([
            'created_at','src_ip','dst_ip','risk_level','prob_attack','tot_fwd_pkts','flow_bytes_s'
        ]);

        $data = $rows->map(function ($r) {
            return [
                'created_at' => $r->created_at->toDateTimeString(),
                'src_ip' => $r->src_ip,
                'dst_ip' => $r->dst_ip,
                'risk_level' => $r->risk_level,
                'prob_attack' => $r->prob_attack,
                'tot_fwd_pkts' => $r->tot_fwd_pkts,
                'flow_bytes_s' => $r->flow_bytes_s,
            ];
        });

        return response()->json(['rows' => $data]);
    }

    public function export(Request $request)
    {
        $window = $request->query('window', '24h');

        $query = IntrusionLog::query();
        switch ($window) {
            case '24h':
                $query->where('created_at', '>=', Carbon::now()->subDay());
                break;
            case '7d':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
            case '30d':
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
                break;
            case 'all':
            default:
                break;
        }

        $filename = 'threat-reports-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['timestamp','src_ip','dst_ip','risk_level','prob_attack','tot_fwd_pkts','flow_bytes_s']);
            foreach ($query->orderBy('created_at')->cursor() as $row) {
                fputcsv($handle, [
                    $row->created_at->toDateTimeString(),
                    $row->src_ip ?? '',
                    $row->dst_ip ?? '',
                    $row->risk_level ?? '',
                    $row->prob_attack ?? '',
                    $row->tot_fwd_pkts ?? '',
                    $row->flow_bytes_s ?? '',
                ]);
            }
            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
