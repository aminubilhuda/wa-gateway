<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageLog;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function contacts(): JsonResponse
    {
        $contacts = Contact::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    public function campaigns(): JsonResponse
    {
        $campaigns = Campaign::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    public function devices(): JsonResponse
    {
        $devices = Device::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    public function sendMessage(Request $request, WhatsAppService $whatsapp): JsonResponse
    {
        $validated = $request->validate([
            'target' => 'required|string',
            'message' => 'required|string',
            'url' => 'nullable|string',
        ]);

        $response = $whatsapp->sendMessage($validated['target'], $validated['message'], $validated['url'] ?? null);

        return response()->json($response);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_contacts' => Contact::count(),
                'total_sent' => MessageLog::where('status', 'sent')->count(),
                'total_campaigns' => Campaign::count(),
                'connected_devices' => Device::where('status', 'connected')->count(),
            ],
        ]);
    }
}
