<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\IntrusionLog;
use App\Models\ThreatIncident;

class ManageThreatController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;
        $query = ThreatIncident::query()->with('latestLog');

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

        if ($request->filled('date')) {
            $query->whereDate('last_detected_at', $request->input('date'));
        }

        $status = $request->input('status', 'unresolved');
        if (in_array($status, ['unresolved', 'blocked', 'resolved'], true)) {
            $query->where('status', $status);
        }

        $incidents = $query->orderByDesc('last_detected_at')->orderByDesc('id')
            ->paginate($perPage)->withQueryString();
        $incidents->getCollection()->transform(function (ThreatIncident $incident) {
            $threat = $incident->latestLog ? clone $incident->latestLog : new IntrusionLog();
            $threat->setAttribute('incident_id', $incident->id);
            $threat->setAttribute('pppoe_name', $incident->pppoe_name);
            $threat->setAttribute('attack_type', $incident->attack_type);
            $threat->setAttribute('event_count', $incident->event_count);
            $threat->setAttribute('group_status', $incident->status);
            $threat->setAttribute('created_at', $incident->last_detected_at);
            return $threat;
        });
        $threats = $incidents;

        $hasNotes = in_array('notes', $allCols, true);
        $demoSeedTargets = [
            [
                'name' => 'pppoe-jimson',
                'src_ip' => '10.0.70.2',
                'pppoe_name' => 'jimson',
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
                'pppoe_name' => 'jimson2',
                'dst_ip' => optional(
                    IntrusionLog::where('src_ip', '10.0.70.5')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->first()
                )->dst_ip ?? '10.0.70.9',
            ],
        ];

        $hasPppoeName = in_array('pppoe_name', $allCols, true);

        return view('admin.manage-threats', compact('threats', 'typeColumn', 'hasNotes', 'hasPppoeName', 'demoSeedTargets'));
    }

    public function block(Request $request, $id)
    {
        $incident = ThreatIncident::findOrFail($id);
        abort_if(blank($incident->pppoe_name), 422, 'A PPPoE identity is required to block safely.');
        $incident->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'restored_at' => null,
            'firewall_synced_at' => null,
        ]);

        return redirect()->back()->with('success', "Subscriber {$incident->pppoe_name} has been marked as blocked.");
    }

    public function ignore(Request $request, $id)
    {
        $incident = ThreatIncident::findOrFail($id);
        abort_if(blank($incident->pppoe_name), 422, 'A PPPoE identity is required to restore safely.');
        $incident->update([
            'status' => 'resolved',
            'restored_at' => now(),
            'firewall_synced_at' => null,
        ]);

        return redirect()->back()->with('success', "Internet access for subscriber {$incident->pppoe_name} has been marked for restoration.");
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (!$action || empty($ids)) {
            return redirect()->back()->with('error', 'Invalid request.');
        }

        $status = $action === 'block' ? 'blocked' : 'resolved';
        $timestampColumn = $status === 'blocked' ? 'blocked_at' : 'restored_at';
        ThreatIncident::whereIn('id', $ids)->whereNotNull('pppoe_name')->update([
            'status' => $status,
            $timestampColumn => now(),
            'firewall_synced_at' => null,
        ]);

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
                'pppoe_name' => 'jimson',
                'src_ip' => '10.0.70.2',
                'dst_ip' => $request->input('dst_ip_jimson'),
                'notes' => 'Dummy attacker from <pppoe-jimson> main',
            ],
            [
                'name' => 'pppoe-jimson2',
                'pppoe_name' => 'jimson2',
                'src_ip' => '10.0.70.5',
                'dst_ip' => $request->input('dst_ip_jimson2'),
                'notes' => 'Dummy attacker from <pppoe-jimson2> main',
            ],
        ];

        foreach ($demoThreats as $threat) {
            $incident = ThreatIncident::create([
                'pppoe_name' => $threat['pppoe_name'],
                'attack_type' => 'demo attacker',
                'status' => 'unresolved',
                'event_count' => 1,
                'first_detected_at' => now(),
                'last_detected_at' => now(),
            ]);

            $log = IntrusionLog::create([
                'incident_id' => $incident->id,
                'src_ip' => $threat['src_ip'],
                'pppoe_name' => $threat['pppoe_name'],
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

}
