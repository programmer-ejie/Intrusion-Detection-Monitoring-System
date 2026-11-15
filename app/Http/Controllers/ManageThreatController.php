<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\IntrusionLog;
use App\Models\ThreatIncident;
use App\Services\DemoAttackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

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

        $demoAttackCatalog = app(DemoAttackService::class)->catalog();

        return view('admin.manage-threats', compact('threats', 'typeColumn', 'hasNotes', 'hasPppoeName', 'demoSeedTargets', 'demoAttackCatalog'));
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
        // On Windows, PHP's execution limit includes time spent waiting for HTTP.
        // Let the bounded Render timeout finish before PHP terminates the request.
        $executionLimit = (int) ini_get('max_execution_time');
        if ($executionLimit > 0 && $executionLimit < 240) {
            set_time_limit(240);
        }
        $service = app(DemoAttackService::class);
        $catalog = $service->catalog();
        $validated = $request->validate([
            'pppoe_name' => ['required', Rule::in(['jimson', 'jimson2'])],
            'dst_ip' => ['required', 'ipv4'],
            'attack_type' => ['required', Rule::in(array_keys($catalog))],
            'sample_index' => ['required', 'integer', 'min:0'],
        ]);
        $samples = $catalog[$validated['attack_type']];
        abort_unless(isset($samples[$validated['sample_index']]), 422, 'Invalid attack sample.');
        $features = $samples[$validated['sample_index']];
        $payload = array_merge($features, [
            'src_ip' => $validated['pppoe_name'] === 'jimson' ? '10.0.70.2' : '10.0.70.5',
            'pppoe_name' => $validated['pppoe_name'],
            'dst_ip' => $validated['dst_ip'],
            'src_port' => random_int(49152, 65535),
            'dst_port' => (int) $features['destination_port'],
            'protocol' => 6,
        ]);
        // Supply both model features and the existing log/display field names.
        foreach ([
            'flow_pkts_s' => 'flow_packets_s',
            'tot_fwd_pkts' => 'total_fwd_packets',
            'tot_bwd_pkts' => 'total_backward_packets',
            'tot_fwd_bytes' => 'total_length_of_fwd_packets',
            'tot_bwd_bytes' => 'total_length_of_bwd_packets',
            'fwd_pkt_len_mean' => 'fwd_packet_length_mean',
            'bwd_pkt_len_mean' => 'bwd_packet_length_mean',
        ] as $alias => $feature) {
            $payload[$alias] = $features[$feature];
        }
        $lastLogId = IntrusionLog::max('id') ?? 0;
        try {
            $result = $service->predict($payload);
        } catch (ConnectionException $exception) {
            return back()->withInput()->with('error', 'Render could not be reached or timed out. No prediction was confirmed; check the threats list before trying again.');
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        DB::transaction(function () use ($payload, $result, $lastLogId) {
            // Reuse a log saved synchronously by Render or its Laravel callback.
            $log = IntrusionLog::where('id', '>', $lastLogId)
                ->where('pppoe_name', $payload['pppoe_name'])
                ->where('src_ip', $payload['src_ip'])->where('dst_ip', $payload['dst_ip'])
                ->where('src_port', $payload['src_port'])->where('dst_port', $payload['dst_port'])
                ->orderByDesc('id')->lockForUpdate()->first();
            if (!$log) {
                $log = new IntrusionLog();
                $log->fill($payload);
            }
            $log->fill(array_intersect_key($result, array_flip(['risk_level', 'prob_attack', 'attack_type'])));
            if ($result['risk_level'] === 'attack' && !$log->incident_id) {
                $incident = ThreatIncident::where('pppoe_name', $payload['pppoe_name'])
                    ->where('attack_type', $result['attack_type'])
                    ->where(function ($query) {
                        $query->where('status', 'blocked')->orWhere(function ($query) {
                            $query->where('status', 'unresolved')->where('last_detected_at', '>=', now()->subMinutes(15));
                        });
                    })->orderByRaw("CASE WHEN status = 'blocked' THEN 0 ELSE 1 END")
                    ->orderByDesc('last_detected_at')->lockForUpdate()->first();
                if ($incident) {
                    $incident->increment('event_count');
                    $incident->update(['last_detected_at' => now()]);
                } else {
                    $incident = ThreatIncident::create([
                        'pppoe_name' => $payload['pppoe_name'],
                        'attack_type' => $result['attack_type'],
                        'status' => 'unresolved', 'event_count' => 1,
                        'first_detected_at' => now(), 'last_detected_at' => now(),
                    ]);
                }
                $log->incident_id = $incident->id;
            }
            $log->save();
        });
        $request->session()->put('demo_last_samples.'.$validated['attack_type'], (int) $validated['sample_index']);

        return redirect()->route('admin.manage-threats')->with('success', 'Demo flow analyzed. Render predicted '.($result['attack_type'] ?? 'benign').'.')
            ->with('demo_prediction', ['selected_attack' => $validated['attack_type'], 'payload' => $payload, 'result' => $result]);
    }
}
