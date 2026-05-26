<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageLog;
use App\Services\WhatsAppService;

class DashboardController extends Controller
{
    public function index(WhatsAppService $whatsapp)
    {
        $totalContacts = Contact::count();
        $totalSent = MessageLog::where('status', 'sent')->count();
        $successRate = $totalSent > 0 ? round(($totalSent / MessageLog::count()) * 100, 1) : 0;
        $activeCampaigns = Campaign::whereIn('status', ['running', 'scheduled'])->count();

        $recentLogs = MessageLog::with(['contact', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // All devices from DB
        $devices = Device::orderBy('created_at', 'desc')->get();

        $totalDevices = $devices->count();
        $connectedDevices = $devices->where('status', 'connected')->count();

        // Weekly chart performance (last 7 days)
        $chartData = [];
        $maxCount = 0;

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D');

            $sent = MessageLog::whereDate('sent_at', $date)->where('status', 'sent')->count();
            $failed = MessageLog::whereDate('sent_at', $date)->where('status', 'failed')->count();

            $maxCount = max($maxCount, $sent, $failed);

            $chartData[] = [
                'day' => $dayName,
                'sent' => $sent,
                'failed' => $failed,
            ];
        }

        $divisor = $maxCount === 0 ? 1 : $maxCount;
        foreach ($chartData as &$data) {
            $data['sent_height'] = round(($data['sent'] / $divisor) * 100);
            $data['failed_height'] = round(($data['failed'] / $divisor) * 100);
        }

        return view('dashboard', compact(
            'totalContacts',
            'totalSent',
            'successRate',
            'activeCampaigns',
            'recentLogs',
            'devices',
            'totalDevices',
            'connectedDevices',
            'chartData'
        ));
    }
}
