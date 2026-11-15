<?php

namespace App\Http\Controllers;

use App\Models\IntrusionLog;
use App\Services\ThreatReportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class ThreatReportsController extends Controller
{
    public function index(Request $request, ThreatReportService $reports)
    {
        $query = $reports->query($request);
        $summary = $reports->summary($query);

        return view('admin.threat-reports', $summary + [
            'window' => $request->query('window') ?: 'all',
            'total' => $summary['counts']->total,
            'attacks' => $summary['counts']->attacks,
            'highRisk' => $summary['counts']->high_risk,
            'latest' => (clone $query)->latest()->first(),
            'rows' => (clone $query)->latest()->orderByDesc('id')->paginate(10)->withQueryString(),
        ]);
    }

    public function review(Request $request, IntrusionLog $log)
    {
        abort_unless($request->session()->get('user')?->canManageThreats(), 403);
        $values = $request->validate([
            'actual_label' => 'required|in:attack,benign',
            'review_notes' => 'required|string|min:5|max:2000',
        ]);
        $log->forceFill($values + [
            'reviewed_by' => $request->session()->get('user')->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('success', 'Event review saved. Report outcomes have been updated.');
    }

    public function pdf(Request $request, ThreatReportService $reports)
    {
        $query = $reports->query($request);
        $summary = $reports->summary($query);
        $clientQuery = (clone $query)->selectRaw(ThreatReportService::CLIENT)
            ->where(fn ($q) => $q->where('risk_level', 'attack')->orWhere('actual_label', 'attack'))->distinct();
        $evidence = (clone $query)->whereIn(\Illuminate\Support\Facades\DB::raw(ThreatReportService::CLIENT), $clientQuery);
        $evidenceCount = (clone $evidence)->count();
        // Bound Dompdf's table layout memory on standard 128 MB PHP workers.
        $rows = $evidence->latest()->orderByDesc('id')->limit(100)->get();
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('admin.reports.threat-pdf', $summary + [
            'rows' => $rows, 'evidenceCount' => $evidenceCount,
            'window' => $request->query('window') ?: 'all',
            'clientFilter' => $request->query('client'), 'generatedAt' => now('Asia/Manila'),
        ])->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $pdf->getCanvas()->page_text(745, 570, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="threat-report-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function data(Request $request, ThreatReportService $reports)
    {
        $query = $reports->query($request);
        if ($request->filled('q')) {
            $q = $request->validate(['q' => 'string|max:255'])['q'];
            $query->where(fn ($sub) => $sub->where('src_ip', 'like', "%{$q}%")
                ->orWhere('dst_ip', 'like', "%{$q}%")->orWhere('pppoe_name', 'like', "%{$q}%"));
        }

        return response()->json(['rows' => $query->latest()->limit(200)->get()]);
    }

    public function export(Request $request, ThreatReportService $reports)
    {
        $query = $reports->query($request);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['event_id', 'timestamp', 'client', 'src_ip', 'dst_ip', 'risk_level', 'attack_type', 'prob_attack', 'tot_fwd_pkts', 'flow_bytes_s', 'actual_label', 'outcome', 'review_notes', 'reviewed_by', 'reviewed_at']);
            foreach ($query->orderBy('created_at')->orderBy('id')->cursor() as $row) {
                $values = [$row->id, $row->created_at, $row->pppoe_name, $row->src_ip, $row->dst_ip,
                    $row->risk_level, $row->attack_type, $row->prob_attack, $row->tot_fwd_pkts,
                    $row->flow_bytes_s, $row->actual_label, ThreatReportService::outcome($row),
                    $row->review_notes, $row->reviewed_by, $row->reviewed_at];
                fputcsv($handle, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, $values));
            }
            fclose($handle);
        }, 'threat-reports-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
