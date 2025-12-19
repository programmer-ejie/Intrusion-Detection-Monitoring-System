<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        // Filter by status (default to unresolved)
        $status = $request->input('status', 'unresolved');
        if ($status === 'unresolved') {
            $query->whereNull('status');
        } elseif ($status === 'blocked' || $status === 'resolved') {
            $query->where('status', $status);
        }

        // Get threats and group by source IP
        $threats = $query->orderBy('created_at', 'desc')
                        ->orderBy('src_ip')
                        ->paginate($perPage)
                        ->withQueryString();

        $hasNotes = in_array('notes', $allCols, true);

        return view('admin.manage-threats', compact('threats', 'typeColumn', 'hasNotes'));
    }

    public function block(Request $request, $id)
    {
        $log = IntrusionLog::findOrFail($id);
        $srcIp = $log->src_ip;

        // Mark all logs with this source IP as blocked
        IntrusionLog::where('src_ip', $srcIp)->update(['status' => 'blocked']);

        return redirect()->back()->with('success', "All logs from IP {$srcIp} have been blocked.");
    }

    public function ignore(Request $request, $id)
    {
        $log = IntrusionLog::findOrFail($id);
        $log->update(['status' => 'resolved']);

        return redirect()->back()->with('success', "Threat from {$log->src_ip} marked as resolved.");
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (!$action || empty($ids)) {
            return redirect()->back()->with('error', 'Invalid request.');
        }

        $status = $action === 'block' ? 'blocked' : 'resolved';
        IntrusionLog::whereIn('id', $ids)->update(['status' => $status]);

        $count = count($ids);
        $message = $action === 'block' 
            ? "Successfully blocked $count threat(s)."
            : "Successfully resolved $count threat(s).";

        return redirect()->back()->with('success', $message);
    }
}
