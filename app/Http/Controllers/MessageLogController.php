<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class MessageLogController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageLog::with(['contact', 'campaign']);

        // Filter by Campaign
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date Range
        if ($request->filled('date_range')) {
            $range = $request->date_range;
            if ($range === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($range === '7_days') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($range === '30_days') {
                $query->where('created_at', '>=', now()->subDays(30));
            } elseif ($range === 'this_month') {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(10);
        $logs->appends($request->all()); // Preserve filters in pagination links

        // Fetch all campaigns for filter dropdown
        $campaigns = Campaign::orderBy('name', 'asc')->get();

        // Calculate dynamic stats based on current filters
        $statsQuery = clone $query;
        $statsQuery->setEagerLoads([]);

        $totalLogs = $statsQuery->count();
        $successCount = (clone $statsQuery)->where('status', 'sent')->count();
        $failedCount = (clone $statsQuery)->where('status', 'failed')->count();
        $pendingCount = (clone $statsQuery)->where('status', 'pending')->count();

        $successRate = $totalLogs > 0 ? round(($successCount / $totalLogs) * 100, 1) : 0;
        $failedRate = $totalLogs > 0 ? round(($failedCount / $totalLogs) * 100, 1) : 0;
        $pendingRate = $totalLogs > 0 ? round(($pendingCount / $totalLogs) * 100, 1) : 0;

        // Hourly delivery timeline (today)
        $hourlyData = [];
        $maxHourly = 0;
        for ($h = 8; $h <= 20; $h += 2) {
            $count = (clone $statsQuery)->whereHour('created_at', $h)->count();
            $maxHourly = max($maxHourly, $count);
            $hourlyData[] = [
                'hour' => sprintf('%02d:00', $h),
                'count' => $count,
            ];
        }
        foreach ($hourlyData as &$data) {
            $data['height'] = $maxHourly > 0 ? round(($data['count'] / $maxHourly) * 100) : 0;
        }

        return view('reports', compact(
            'logs',
            'campaigns',
            'totalLogs',
            'successCount',
            'failedCount',
            'pendingCount',
            'successRate',
            'failedRate',
            'pendingRate',
            'hourlyData'
        ));
    }

    public function exportCsv(Request $request)
    {
        $query = MessageLog::with(['contact', 'campaign']);

        // Filter by Campaign
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date Range
        if ($request->filled('date_range')) {
            $range = $request->date_range;
            if ($range === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($range === '7_days') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($range === '30_days') {
                $query->where('created_at', '>=', now()->subDays(30));
            } elseif ($range === 'this_month') {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=report_logs_'.date('Y-m-d_H-i-s').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper excel formatting
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Row
            fputcsv($file, [
                'ID Log',
                'Tanggal & Waktu',
                'Nama Kontak',
                'Nomor Telepon',
                'Kampanye',
                'Pesan',
                'Status',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->contact ? $log->contact->name : '-',
                    $log->contact ? $log->contact->phone_number : '-',
                    $log->campaign ? $log->campaign->name : 'Pesan Langsung / Auto Reply',
                    $log->message_body,
                    ucfirst($log->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
