<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntrusionLog;

class IntrusionLogController extends Controller
{
    public function store(Request $request)
    {
        IntrusionLog::create($request->all());

        return response()->json([
            'status' => 'saved'
        ]);
    }
}
