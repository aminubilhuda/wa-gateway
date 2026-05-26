<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookLog::orderBy('created_at', 'desc');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        $logs = $query->paginate(20);

        return view('webhook-logs', compact('logs'));
    }

    public function show(WebhookLog $webhookLog)
    {
        return response()->json($webhookLog);
    }
}
