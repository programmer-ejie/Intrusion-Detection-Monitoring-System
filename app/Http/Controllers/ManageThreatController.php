<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use App\Models\IntrusionLog;

class ManageThreatController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;
        $query = IntrusionLog::query();

        // Get all columns to check for type column
        $allCols = Schema::getColumnListing('intrusion_logs');

        // Determine which column represents threat type
        $candidates = ['is_malicious', 'label', 'type', 'classification', 'verdict', 'attack_type'];
        $typeColumn = null;
        foreach ($candidates as $c) {
            if (in_array($c, $allCols, true)) {
                $typeColumn = $c;
                break;
            }
        }

        // Filter to show only attacks/threats
        if ($typeColumn) {
            if ($typeColumn === 'is_malicious') {
                $query->where($typeColumn, true);
            } else {
                // Show only non-benign/non-normal entries
                $query->where($typeColumn, '!=', 'benign')
                      ->where($typeColumn, '!=', 'normal')
                      ->where($typeColumn, '!=', '');
            }
        }

        // Filter by date if provided
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // Load logs newest-first, then collapse them to one row per destination IP.
        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->get();
        $threats = $logs->groupBy('dst_ip')->map(function ($items) {
            $latest = clone $items->first();
            $latest->setAttribute('event_count', $items->count());
            $latest->setAttribute('group_status', $this->resolveGroupStatus($items));
            return $latest;
        });

        // Filter by status after grouping so one IP still appears only once.
        $status = $request->input('status', 'unresolved');
        if ($status === 'unresolved') {
            $threats = $threats->filter(fn ($threat) => blank($threat->group_status));
        } elseif (in_array($status, ['blocked', 'resolved'], true)) {
            $threats = $threats->filter(fn ($threat) => $threat->group_status === $status);
        }

        $threats = $threats->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagedThreats = $threats->slice(($page - 1) * $perPage, $perPage)->values();
        $threats = new LengthAwarePaginator($pagedThreats, $threats->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $hasNotes = in_array('notes', $allCols, true);
        $demoSeedTargets = [
            [
                'name' => 'pppoe-jimson',
                'src_ip' => '10.0.70.2',
                'dst_ip' => optional(
                    IntrusionLog::where('src_ip', '10.0.70.2')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->first()
                )->dst_ip ?? '10.0.70.4',
            ],
            [
                'name' => 'pppoe-jimson2',
                'src_ip' => '10.0.70.5',
                'dst_ip' => optional(
                    IntrusionLog::where('src_ip', '10.0.70.5')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->first()
                )->dst_ip ?? '10.0.70.9',
            ],
        ];

        return view('admin.manage-threats', compact('threats', 'typeColumn', 'hasNotes', 'demoSeedTargets'));
    }

    public function block(Request $request, $id)
    {
        $log = IntrusionLog::findOrFail($id);
        $dstIp = $log->dst_ip;

        // Mark all logs with this destination IP as blocked so the local Python/MikroTik agent can sync them.
        IntrusionLog::where('dst_ip', $dstIp)->update(['status' => 'blocked']);

        return redirect()->back()->with('success', "All logs for destination IP {$dstIp} have been marked as blocked.");
    }

    public function ignore(Request $request, $id)
    {
        $log = IntrusionLog::findOrFail($id);
        IntrusionLog::where('dst_ip', $log->dst_ip)->update(['status' => 'resolved']);

        return redirect()->back()->with('success', "Internet access for destination IP {$log->dst_ip} has been restored.");
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (!$action || empty($ids)) {
            return redirect()->back()->with('error', 'Invalid request.');
        }

        $status = $action === 'block' ? 'blocked' : 'resolved';
        $dstIps = IntrusionLog::whereIn('id', $ids)->pluck('dst_ip')->filter()->unique()->values();
        IntrusionLog::whereIn('dst_ip', $dstIps)->update(['status' => $status]);

        $count = count($ids);
        $message = $action === 'block' 
            ? "Successfully marked $count threat(s) as blocked."
            : "Successfully resolved $count threat(s).";

        return redirect()->back()->with('success', $message);
    }

    public function demo(Request $request)
    {
        $request->validate([
            'dst_ip_jimson' => ['required', 'ip'],
            'dst_ip_jimson2' => ['required', 'ip'],
        ]);

        $demoThreats = [
            [
                'name' => 'pppoe-jimson',
                'src_ip' => '10.0.70.2',
                'dst_ip' => $request->input('dst_ip_jimson'),
                'notes' => 'Dummy attacker from <pppoe-jimson> main',
            ],
            [
                'name' => 'pppoe-jimson2',
                'src_ip' => '10.0.70.5',
                'dst_ip' => $request->input('dst_ip_jimson2'),
                'notes' => 'Dummy attacker from <pppoe-jimson2> main',
            ],
        ];

        foreach ($demoThreats as $threat) {
            IntrusionLog::where('src_ip', $threat['src_ip'])
                ->where('attack_type', 'demo attacker')
                ->update([
                    'dst_ip' => $threat['dst_ip'],
                ]);

            $log = IntrusionLog::create([
                'src_ip' => $threat['src_ip'],
                'dst_ip' => $threat['dst_ip'],
                'src_port' => 49512,
                'dst_port' => 80,
                'protocol' => 6,
                'flow_duration' => 12.48,
                'flow_pkts_s' => 184.25,
                'flow_bytes_s' => 2048.75,
                'tot_fwd_pkts' => 48,
                'tot_bwd_pkts' => 19,
                'tot_fwd_bytes' => 9216,
                'tot_bwd_bytes' => 3072,
                'fwd_pkt_len_mean' => 192.0,
                'bwd_pkt_len_mean' => 161.3,
                'fwd_iat_mean' => 0.0048,
                'bwd_iat_mean' => 0.0062,
                'risk_level' => 'attack',
                'prob_attack' => 0.99,
                'attack_type' => 'demo attacker',
            ]);

            if (Schema::hasColumn('intrusion_logs', 'notes')) {
                $log->notes = $threat['notes'];
                $log->save();
            }
        }

        return redirect()
            ->route('admin.manage-threats')
            ->with('success', 'Dummy attacker threats added to the threats list.');
    }

    private function resolveGroupStatus($items): ?string
    {
        $statuses = $items->pluck('status')->filter()->values();

        if ($statuses->contains('blocked')) {
            return 'blocked';
        }

        if ($statuses->contains('resolved')) {
            return 'resolved';
        }

        return null;
    }
}
