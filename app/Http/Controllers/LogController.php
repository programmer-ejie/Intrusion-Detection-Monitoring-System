<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\IntrusionLog;

class LogController extends Controller
{
    public function gotoLogs(Request $request)
    {
        $perPage = 3;
        $query = IntrusionLog::query();

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $allCols = Schema::getColumnListing('intrusion_logs');

        $candidates = ['is_malicious', 'label', 'type', 'classification', 'verdict', 'attack_type'];
        $typeColumn = null;
        foreach ($candidates as $c) {
            if (in_array($c, $allCols, true)) {
                $typeColumn = $c;
                break;
            }
        }

        if ($request->filled('type') && $typeColumn) {
            $type = strtolower($request->input('type'));
            if ($typeColumn === 'is_malicious') {
                if ($type === 'normal' || $type === 'benign') {
                    $query->where($typeColumn, false);
                } elseif ($type === 'attack') {
                    $query->where($typeColumn, true);
                }
            } else {
                if ($type === 'normal' || $type === 'benign') {
                    $query->where($typeColumn, 'benign')->orWhere($typeColumn, 'normal');
                } elseif ($type === 'attack') {
                    $query->where($typeColumn, '!=', 'benign')->where($typeColumn, '!=', 'normal');
                }
            }
        }

        $possibleSearchCols = ['src_ip', 'dst_ip', 'protocol', 'notes', 'payload', 'info', 'src_port', 'dst_port'];
        $searchCols = array_values(array_intersect($possibleSearchCols, $allCols));
        if (count($searchCols) === 0) {
            $fallback = array_diff($allCols, ['id', 'created_at', 'updated_at']);
            $searchCols = array_slice(array_values($fallback), 0, 6);
        }

        if ($request->filled('q') && count($searchCols) > 0) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q, $searchCols) {
                foreach ($searchCols as $idx => $col) {
                    if ($idx === 0) {
                        $sub->where($col, 'like', "%{$q}%");
                    } else {
                        $sub->orWhere($col, 'like', "%{$q}%");
                    }
                }
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $hasNotes = in_array('notes', $allCols, true);

        return view('admin.logs', compact('logs', 'typeColumn', 'searchCols', 'hasNotes'));
    }
}