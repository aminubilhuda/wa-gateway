<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Services\FonnteService;

class DashboardController extends Controller
{
    public function index(FonnteService $fonnte)
    {
        $totalContacts = Contact::count();
        $totalSent = MessageLog::where('status', 'sent')->count();
        $successRate = $totalSent > 0 ? round(($totalSent / MessageLog::count()) * 100, 1) : 0;
        $activeCampaigns = Campaign::whereIn('status', ['running', 'scheduled'])->count();

        $recentLogs = MessageLog::with(['contact', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Device / connection status
        $deviceStatus = $fonnte->getDeviceStatus();
        $isConnected = false;
        $deviceNumber = '-';
        $deviceQuota = '0';
        $deviceStatusMsg = 'Disconnected';

        if (isset($deviceStatus['status']) && $deviceStatus['status'] == true) {
            $deviceNumber = $deviceStatus['device'] ?? $deviceStatus['sender'] ?? '-';
            $deviceQuota = $deviceStatus['quota'] ?? '0';
            $deviceStatusMsg = ucfirst($deviceStatus['device_status'] ?? 'Connected');
            if (strtolower($deviceStatusMsg) === 'connected' || strtolower($deviceStatusMsg) === 'connect') {
                $isConnected = true;
            }
        }

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
            'isConnected',
            'deviceNumber',
            'deviceQuota',
            'deviceStatusMsg',
            'chartData'
        ));
    }
}
