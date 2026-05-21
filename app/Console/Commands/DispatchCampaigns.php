<?php

namespace App\Console\Commands;

use App\Models\Blacklist;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageLog;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch';

    protected $description = 'Dispatch scheduled campaigns that are due';

    public function handle(FonnteService $fonnte): int
    {
        $now = now();
        $currentTimeString = $now->format('H:i');
        $currentDayOfWeek = $now->dayOfWeekIso;
        $currentDayOfMonth = $now->day;

        $campaigns = Campaign::where('is_active', true)
            ->where('status', 'scheduled')
            ->get()
            ->filter(function ($campaign) use ($now, $currentTimeString, $currentDayOfWeek, $currentDayOfMonth) {
                if (in_array($campaign->schedule_type, ['daily', 'weekly', 'monthly'])) {
                    if ($campaign->last_run_at && Carbon::parse($campaign->last_run_at)->diffInHours($now) < 23) {
                        return false;
                    }
                }

                return match ($campaign->schedule_type) {
                    'once' => $campaign->scheduled_at && Carbon::parse($campaign->scheduled_at)->lte($now),
                    'daily' => $campaign->scheduled_time && $currentTimeString >= $campaign->scheduled_time,
                    'weekly' => $campaign->scheduled_day_of_week === $currentDayOfWeek
                        && $campaign->scheduled_time
                        && $currentTimeString >= $campaign->scheduled_time,
                    'monthly' => $campaign->scheduled_day_of_month === $currentDayOfMonth
                        && $campaign->scheduled_time
                        && $currentTimeString >= $campaign->scheduled_time,
                    'minute' => ! $campaign->last_run_at || Carbon::parse($campaign->last_run_at)->diffInMinutes($now) >= ((int) $campaign->interval_value ?: 1),
                    'hour' => ! $campaign->last_run_at || Carbon::parse($campaign->last_run_at)->diffInHours($now) >= ((int) $campaign->interval_value ?: 1),
                    default => false,
                };
            });

        if ($campaigns->isEmpty()) {
            return Command::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->dispatchCampaign($campaign, $fonnte);
        }

        return Command::SUCCESS;
    }

    protected function dispatchCampaign(Campaign $campaign, FonnteService $fonnte): void
    {
        $campaign->update([
            'status' => 'running',
            'last_run_at' => now(),
        ]);

        $recipientData = $this->getRecipients($campaign);

        $devices = Device::where('status', 'connected')->whereNotNull('token')->get();
        if ($devices->isEmpty()) {
            $devices = Device::whereNotNull('token')->get();
        }

        if ($devices->isEmpty()) {
            $campaign->update(['status' => 'failed']);

            return;
        }

        $deviceBatches = [];
        foreach ($devices as $d) {
            $deviceBatches[$d->id] = [
                'device' => $d,
                'bulkData' => [],
                'logIds' => [],
            ];
        }

        $deviceKeys = array_keys($deviceBatches);
        $i = 0;

        foreach ($recipientData as $data) {
            $contact = Contact::firstOrCreate(
                ['phone_number' => $data['number']],
                ['name' => 'Recipient '.$data['number'], 'label' => 'Imported']
            );

            $selectedDeviceId = $deviceKeys[$i % count($deviceKeys)];
            $currentDevice = $deviceBatches[$selectedDeviceId]['device'];

            $item = [
                'target' => $data['number'],
                'message' => $data['message'],
                'delay' => $currentDevice->delay_seconds ?: '2-5',
            ];

            if ($campaign->attachment_path) {
                $item['url'] = $campaign->attachment_path;
            }

            $deviceBatches[$selectedDeviceId]['bulkData'][] = $item;

            $log = MessageLog::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'message_body' => $data['message'],
                'status' => 'pending',
                'sent_at' => now(),
            ]);

            $deviceBatches[$selectedDeviceId]['logIds'][] = $log->id;
            $i++;
        }

        $allBatchesSuccess = true;
        $totalSent = 0;

        foreach ($deviceBatches as $batch) {
            $bulk = $batch['bulkData'];
            $logs = $batch['logIds'];
            $device = $batch['device'];

            if (count($bulk) > 0) {
                $fonnte->setToken($device->token);
                $response = $fonnte->sendBulkMessages($bulk);

                if (isset($response['status']) && $response['status'] == true) {
                    MessageLog::whereIn('id', $logs)->update(['status' => 'sent']);
                    $totalSent += count($bulk);
                } else {
                    MessageLog::whereIn('id', $logs)->update(['status' => 'failed']);
                    $allBatchesSuccess = false;
                }
            }
        }

        $sentSuccess = ($totalSent > 0 || count($recipientData) === 0) && $allBatchesSuccess;

        if ($campaign->schedule_type === 'once') {
            $campaign->update(['status' => $sentSuccess ? 'completed' : 'failed']);
        } else {
            $campaign->update(['status' => 'scheduled']);
        }
    }

    protected function getRecipients(Campaign $campaign): array
    {
        $recipientData = [];

        if ($campaign->target_type === 'excel' && str_starts_with($campaign->target_value, '[')) {
            $rows = json_decode($campaign->target_value, true);
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $number = $row['A'] ?? null;
                    if (! $number) {
                        continue;
                    }

                    $cleanNumber = $this->normalizePhone($number);

                    if (Blacklist::where('phone_number', $cleanNumber)->exists()) {
                        continue;
                    }

                    $message = $campaign->message_template;
                    foreach ($row as $col => $val) {
                        $message = str_replace('{{'.strtoupper($col).'}}', $val, $message);
                    }
                    $message = str_replace('[Name]', $row['B'] ?? ('Recipient '.$number), $message);

                    $recipientData[] = [
                        'number' => $cleanNumber,
                        'message' => $message,
                    ];
                }
            }
        } else {
            $recipientNumbers = $campaign->getRecipientNumbers();
            foreach ($recipientNumbers as $number) {
                $number = trim($number);
                if (empty($number)) {
                    continue;
                }

                $cleanNumber = $this->normalizePhone($number);

                if (Blacklist::where('phone_number', $cleanNumber)->exists()) {
                    continue;
                }

                $contact = Contact::firstOrCreate(
                    ['phone_number' => $cleanNumber],
                    ['name' => 'Recipient '.$number, 'label' => 'Imported']
                );

                $message = str_replace('[Name]', $contact->name, $campaign->message_template);

                $recipientData[] = [
                    'number' => $cleanNumber,
                    'message' => $message,
                ];
            }
        }

        return $recipientData;
    }

    protected function normalizePhone(string $number): string
    {
        $cleanNumber = preg_replace('/[^\d+]/', '', trim($number));

        if (str_starts_with($cleanNumber, '0')) {
            $cleanNumber = '62'.substr($cleanNumber, 1);
        } elseif (str_starts_with($cleanNumber, '+')) {
            $cleanNumber = substr($cleanNumber, 1);
        } elseif (str_starts_with($cleanNumber, '8')) {
            $cleanNumber = '628'.substr($cleanNumber, 1);
        }

        return $cleanNumber;
    }
}
