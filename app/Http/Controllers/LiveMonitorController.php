<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LiveMonitorController extends Controller
{
    public function gotoLive(Request $request)
    {
        // placeholder data for future live metrics
        $placeholder = [
            'status' => 'Live feed connected',
            'last_update' => now()->format('M d, Y H:i:s'),
        ];

        return view('admin.live-monitor', compact('placeholder'));
    }
}
