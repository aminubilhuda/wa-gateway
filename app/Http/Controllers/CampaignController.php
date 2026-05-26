<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::whereNull('archived_at')->orderBy('created_at', 'desc')->paginate(10);
        $labels = Contact::whereNotNull('label')->where('label', '!=', '')->distinct()->pluck('label');
        $templates = MessageTemplate::orderBy('title', 'asc')->get();

        return view('campaigns', compact('campaigns', 'labels', 'templates'));
    }

    public function store(Request $request, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'is_scheduled' => 'nullable|string',
            'schedule_type' => 'nullable|string|in:once,daily,weekly,monthly,minute,hour',
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => 'nullable|string',
            'scheduled_day_of_week' => 'nullable|integer|between:1,7',
            'scheduled_day_of_month' => 'nullable|integer|between:1,31',
            'interval_value' => 'nullable|integer|min:1',
            'is_active' => 'nullable|string',
            'target_type' => 'required|string|in:all,manual,group,random,excel',
            'manual_numbers' => 'nullable|string',
            'group_label' => 'nullable|string',
            'random_source' => 'nullable|string|in:db,generate',
            'random_db_count' => 'nullable|integer|min:1',
            'random_gen_prefix' => 'nullable|string',
            'random_gen_count' => 'nullable|integer|min:1',
            'excel_file' => 'nullable|file',
            'attachment' => 'nullable|file|max:16384',
        ]);

        // 1. Resolve Target Type & Target Value
        $targetType = $request->input('target_type', 'all');
        $targetValue = null;

        if ($targetType === 'manual') {
            $targetValue = $request->input('manual_numbers');
        } elseif ($targetType === 'group') {
            $targetValue = $request->input('group_label');
        } elseif ($targetType === 'random') {
            $source = $request->input('random_source', 'db');
            if ($source === 'db') {
                $targetValue = $request->input('random_db_count', 5);
            } else {
                // Generate nomor acak langsung dan simpan sebagai manual list
                $prefix = $request->input('random_gen_prefix', '62812');
                $count = (int) $request->input('random_gen_count', 10);
                $generated = [];
                for ($i = 0; $i < $count; $i++) {
                    $generated[] = $prefix.mt_rand(10000000, 99999999);
                }
                $targetType = 'manual';
                $targetValue = implode(',', $generated);
            }
        } elseif ($targetType === 'excel' && $request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            $rows = $this->parseCsvToColumns($file->getRealPath());
            $targetValue = json_encode($rows);
        }

        // 2. Handle File Attachment Upload
        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time().'_'.preg_replace('/[^a-zA-Z0-9_.-]/', '', $file->getClientOriginalName());

            if (! file_exists(public_path('uploads/attachments'))) {
                mkdir(public_path('uploads/attachments'), 0777, true);
            }

            $file->move(public_path('uploads/attachments'), $filename);
            $attachmentPath = asset('uploads/attachments/'.$filename);

            $mime = $file->getClientMimeType();
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $attachmentType = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'file';
            }
        }

        // 3. Process Scheduling Options
        $isScheduled = $request->input('is_scheduled') === 'on';
        $status = $isScheduled ? 'scheduled' : 'running';
        $isActive = $request->has('is_scheduled') ? $request->has('is_active') : true;

        $scheduleType = $request->input('schedule_type', 'once');
        $scheduledAt = null;
        $scheduledTime = null;
        $scheduledDayOfWeek = null;
        $scheduledDayOfMonth = null;
        $intervalValue = null;

        if ($isScheduled) {
            if ($scheduleType === 'once' && $request->filled('scheduled_at')) {
                $scheduledAt = Carbon::parse($request->input('scheduled_at'), 'Asia/Jakarta');
            } elseif ($scheduleType === 'daily' && $request->filled('scheduled_time')) {
                $scheduledTime = $request->input('scheduled_time');
            } elseif ($scheduleType === 'weekly' && $request->filled('scheduled_time') && $request->filled('scheduled_day_of_week')) {
                $scheduledTime = $request->input('scheduled_time');
                $scheduledDayOfWeek = (int) $request->input('scheduled_day_of_week');
            } elseif ($scheduleType === 'monthly' && $request->filled('scheduled_time') && $request->filled('scheduled_day_of_month')) {
                $scheduledTime = $request->input('scheduled_time');
                $scheduledDayOfMonth = (int) $request->input('scheduled_day_of_month');
            } elseif (in_array($scheduleType, ['minute', 'hour']) && $request->filled('interval_value')) {
                $intervalValue = (int) $request->input('interval_value');
            }
        }

        // 4. Create Campaign
        $campaign = Campaign::create([
            'name' => $validated['name'],
            'message_template' => $validated['message_template'],
            'status' => $status,
            'is_active' => $isActive,
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'schedule_type' => $isScheduled ? $scheduleType : 'once',
            'scheduled_at' => $scheduledAt,
            'scheduled_time' => $scheduledTime,
            'scheduled_day_of_week' => $scheduledDayOfWeek,
            'scheduled_day_of_month' => $scheduledDayOfMonth,
            'interval_value' => $intervalValue,
        ]);

        // 5. Send Immediately or Redirect with Schedule Info
        if (! $isScheduled) {
            $recipientData = [];

            if ($campaign->target_type === 'excel' && str_starts_with($campaign->target_value, '[')) {
                $rows = json_decode($campaign->target_value, true);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $number = $row['A'] ?? null;
                        if (! $number) {
                            continue;
                        }

                        $message = $campaign->message_template;
                        foreach ($row as $col => $val) {
                            $message = str_replace('{{'.strtoupper($col).'}}', $val, $message);
                        }

                        $message = str_replace('[Name]', $row['B'] ?? ('Recipient '.$number), $message);

                        $recipientData[] = [
                            'number' => $number,
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

                    $contact = Contact::firstOrCreate(
                        ['phone_number' => $number],
                        ['name' => 'Recipient '.$number, 'label' => 'Imported']
                    );

                    $message = str_replace('[Name]', $contact->name, $campaign->message_template);

                    $recipientData[] = [
                        'number' => $number,
                        'message' => $message,
                    ];
                }
            }

            $bulkData = [];
            $logIds = [];

            foreach ($recipientData as $data) {
                $number = $data['number'];
                $message = $data['message'];

                $contact = Contact::firstOrCreate(
                    ['phone_number' => $number],
                    ['name' => 'Recipient '.$number, 'label' => 'Imported']
                );

                $deviceDelay = Device::where('status', 'connected')
                    ->value('delay_seconds') ?? Device::value('delay_seconds') ?? '1-3';

                $item = [
                    'target' => $number,
                    'message' => $message,
                    'delay' => $deviceDelay,
                ];

                if ($campaign->attachment_path) {
                    $item['url'] = $campaign->attachment_path;
                }

                $bulkData[] = $item;

                $log = MessageLog::create([
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'message_body' => $message,
                    'status' => 'pending',
                    'sent_at' => now(),
                ]);
                $logIds[] = $log->id;
            }

            if (count($bulkData) > 0) {
                $response = $whatsapp->sendBulkMessages($bulkData);

                if (isset($response['status']) && $response['status'] == true) {
                    MessageLog::whereIn('id', $logIds)->update(['status' => 'sent']);
                    $campaign->update(['status' => 'completed']);

                    return redirect()->route('campaigns')->with('success', 'Kampanye berhasil dikirim ke '.count($bulkData).' nomor tujuan.');
                } else {
                    MessageLog::whereIn('id', $logIds)->update(['status' => 'failed']);
                    $campaign->update(['status' => 'failed']);

                    return redirect()->route('campaigns')->with('error', 'Gagal mengirim kampanye. WhatsApp Gateway Error.');
                }
            }

            $campaign->update(['status' => 'completed']);

            return redirect()->route('campaigns')->with('success', 'Kampanye disimpan (tidak ada nomor penerima yang ditemukan).');
        }

        // Response for Scheduled Campaign
        $successMsg = 'Jadwal pengiriman berhasil disimpan.';
        if (! $isActive) {
            $successMsg = 'Jadwal pengiriman berhasil disimpan dalam keadaan nonaktif.';
        } elseif ($scheduleType === 'once') {
            $successMsg = 'Jadwal pengiriman berhasil disimpan untuk tanggal '.$scheduledAt->format('d-M-Y H:i').' WIB.';
        } elseif ($scheduleType === 'daily') {
            $successMsg = 'Jadwal pengiriman berhasil disimpan untuk setiap hari pada jam '.$scheduledTime.' WIB.';
        } elseif ($scheduleType === 'weekly') {
            $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            $successMsg = 'Jadwal pengiriman berhasil disimpan untuk setiap hari '.$days[$scheduledDayOfWeek].' pada jam '.$scheduledTime.' WIB.';
        } elseif ($scheduleType === 'monthly') {
            $successMsg = 'Jadwal pengiriman berhasil disimpan untuk setiap tanggal '.$scheduledDayOfMonth.' pada jam '.$scheduledTime.' WIB.';
        } elseif ($scheduleType === 'minute') {
            $successMsg = 'Jadwal pengiriman berhasil disimpan untuk dikirim setiap '.$intervalValue.' menit sekali.';
        } elseif ($scheduleType === 'hour') {
            $successMsg = 'Jadwal pengiriman berhasil disimpan untuk dikirim setiap '.$intervalValue.' jam sekali.';
        }

        return redirect()->route('campaigns')->with('success', $successMsg);
    }

    public function toggle(Campaign $campaign)
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);

        return response()->json(['status' => 'success', 'is_active' => $campaign->is_active]);
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        if ($campaign->attachment_path) {
            $relativePath = str_replace(asset(''), '', $campaign->attachment_path);
            $fullPath = public_path($relativePath);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        $campaign->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Kampanye/Jadwal berhasil dihapus.']);
        }

        return redirect()->route('campaigns')->with('success', 'Kampanye/Jadwal berhasil dihapus.');
    }

    public function archive(Request $request, Campaign $campaign)
    {
        $campaign->update(['archived_at' => now()]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Kampanye berhasil diarsipkan.']);
        }

        return redirect()->route('campaigns')->with('success', 'Kampanye berhasil diarsipkan.');
    }

    public function restore(Request $request, Campaign $campaign)
    {
        $campaign->update(['archived_at' => null]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Kampanye berhasil dikembalikan.']);
        }

        return redirect()->route('campaigns')->with('success', 'Kampanye berhasil dikembalikan.');
    }

    public function archived()
    {
        $campaigns = Campaign::whereNotNull('archived_at')->orderBy('archived_at', 'desc')->paginate(10);
        $labels = Contact::whereNotNull('label')->where('label', '!=', '')->distinct()->pluck('label');
        $templates = MessageTemplate::orderBy('title', 'asc')->get();

        return view('campaigns', compact('campaigns', 'labels', 'templates'));
    }

    public function update(Request $request, Campaign $campaign, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'is_scheduled' => 'nullable|string',
            'schedule_type' => 'nullable|string|in:once,daily,weekly,monthly,minute,hour',
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => 'nullable|string',
            'scheduled_day_of_week' => 'nullable|integer|between:1,7',
            'scheduled_day_of_month' => 'nullable|integer|between:1,31',
            'interval_value' => 'nullable|integer|min:1',
            'is_active' => 'nullable|string',
            'target_type' => 'required|string|in:all,manual,group,random,excel',
            'manual_numbers' => 'nullable|string',
            'group_label' => 'nullable|string',
            'random_source' => 'nullable|string|in:db,generate',
            'random_db_count' => 'nullable|integer|min:1',
            'random_gen_prefix' => 'nullable|string',
            'random_gen_count' => 'nullable|integer|min:1',
            'excel_file' => 'nullable|file',
            'attachment' => 'nullable|file|max:16384',
        ]);

        // 1. Resolve Target Type & Target Value
        $targetType = $request->input('target_type', 'all');
        $targetValue = $campaign->target_value;

        if ($targetType === 'manual') {
            $targetValue = $request->input('manual_numbers');
        } elseif ($targetType === 'group') {
            $targetValue = $request->input('group_label');
        } elseif ($targetType === 'random') {
            $source = $request->input('random_source', 'db');
            if ($source === 'db') {
                $targetValue = $request->input('random_db_count', 5);
            } else {
                $prefix = $request->input('random_gen_prefix', '62812');
                $count = (int) $request->input('random_gen_count', 10);
                $generated = [];
                for ($i = 0; $i < $count; $i++) {
                    $generated[] = $prefix.mt_rand(10000000, 99999999);
                }
                $targetType = 'manual';
                $targetValue = implode(',', $generated);
            }
        } elseif ($targetType === 'excel') {
            if ($request->hasFile('excel_file')) {
                $file = $request->file('excel_file');
                $rows = $this->parseCsvToColumns($file->getRealPath());
                $targetValue = json_encode($rows);
            }
        }

        // 2. Handle File Attachment Upload
        $attachmentPath = $campaign->attachment_path;
        $attachmentType = $campaign->attachment_type;

        if ($request->hasFile('attachment')) {
            if ($campaign->attachment_path) {
                $relativePath = str_replace(asset(''), '', $campaign->attachment_path);
                $fullPath = public_path($relativePath);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $file = $request->file('attachment');
            $filename = time().'_'.preg_replace('/[^a-zA-Z0-9_.-]/', '', $file->getClientOriginalName());

            if (! file_exists(public_path('uploads/attachments'))) {
                mkdir(public_path('uploads/attachments'), 0777, true);
            }

            $file->move(public_path('uploads/attachments'), $filename);
            $attachmentPath = asset('uploads/attachments/'.$filename);

            $mime = $file->getClientMimeType();
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $attachmentType = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'file';
            }
        }

        // 3. Process Scheduling Options
        $isScheduled = $request->input('is_scheduled') === 'on';
        $status = $isScheduled ? 'scheduled' : 'running';
        $isActive = $request->has('is_scheduled') ? $request->has('is_active') : true;

        $scheduleType = $request->input('schedule_type', 'once');
        $scheduledAt = null;
        $scheduledTime = null;
        $scheduledDayOfWeek = null;
        $scheduledDayOfMonth = null;
        $intervalValue = null;

        if ($isScheduled) {
            if ($scheduleType === 'once' && $request->filled('scheduled_at')) {
                $scheduledAt = Carbon::parse($request->input('scheduled_at'), 'Asia/Jakarta');
            } elseif ($scheduleType === 'daily' && $request->filled('scheduled_time')) {
                $scheduledTime = $request->input('scheduled_time');
            } elseif ($scheduleType === 'weekly' && $request->filled('scheduled_time') && $request->filled('scheduled_day_of_week')) {
                $scheduledTime = $request->input('scheduled_time');
                $scheduledDayOfWeek = (int) $request->input('scheduled_day_of_week');
            } elseif ($scheduleType === 'monthly' && $request->filled('scheduled_time') && $request->filled('scheduled_day_of_month')) {
                $scheduledTime = $request->input('scheduled_time');
                $scheduledDayOfMonth = (int) $request->input('scheduled_day_of_month');
            } elseif (in_array($scheduleType, ['minute', 'hour']) && $request->filled('interval_value')) {
                $intervalValue = (int) $request->input('interval_value');
            }
        }

        // 4. Update Campaign
        $campaign->update([
            'name' => $validated['name'],
            'message_template' => $validated['message_template'],
            'status' => $status,
            'is_active' => $isActive,
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'schedule_type' => $isScheduled ? $scheduleType : 'once',
            'scheduled_at' => $scheduledAt,
            'scheduled_time' => $scheduledTime,
            'scheduled_day_of_week' => $scheduledDayOfWeek,
            'scheduled_day_of_month' => $scheduledDayOfMonth,
            'interval_value' => $intervalValue,
        ]);

        // 5. Send Immediately or Redirect
        if (! $isScheduled) {
            $recipientData = [];

            if ($campaign->target_type === 'excel' && str_starts_with($campaign->target_value, '[')) {
                $rows = json_decode($campaign->target_value, true);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $number = $row['A'] ?? null;
                        if (! $number) {
                            continue;
                        }

                        $message = $campaign->message_template;
                        foreach ($row as $col => $val) {
                            $message = str_replace('{{'.strtoupper($col).'}}', $val, $message);
                        }

                        $message = str_replace('[Name]', $row['B'] ?? ('Recipient '.$number), $message);

                        $recipientData[] = [
                            'number' => $number,
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

                    $contact = Contact::firstOrCreate(
                        ['phone_number' => $number],
                        ['name' => 'Recipient '.$number, 'label' => 'Imported']
                    );

                    $message = str_replace('[Name]', $contact->name, $campaign->message_template);

                    $recipientData[] = [
                        'number' => $number,
                        'message' => $message,
                    ];
                }
            }

            $bulkData = [];
            $logIds = [];

            foreach ($recipientData as $data) {
                $number = $data['number'];
                $message = $data['message'];

                $contact = Contact::firstOrCreate(
                    ['phone_number' => $number],
                    ['name' => 'Recipient '.$number, 'label' => 'Imported']
                );

                $deviceDelay = Device::where('status', 'connected')
                    ->value('delay_seconds') ?? Device::value('delay_seconds') ?? '1-3';

                $item = [
                    'target' => $number,
                    'message' => $message,
                    'delay' => $deviceDelay,
                ];

                if ($campaign->attachment_path) {
                    $item['url'] = $campaign->attachment_path;
                }

                $bulkData[] = $item;

                $log = MessageLog::create([
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'message_body' => $message,
                    'status' => 'pending',
                    'sent_at' => now(),
                ]);
                $logIds[] = $log->id;
            }

            if (count($bulkData) > 0) {
                $response = $whatsapp->sendBulkMessages($bulkData);

                if (isset($response['status']) && $response['status'] == true) {
                    MessageLog::whereIn('id', $logIds)->update(['status' => 'sent']);
                    $campaign->update(['status' => 'completed']);

                    return redirect()->route('campaigns')->with('success', 'Kampanye diperbarui dan berhasil dikirim segera ke '.count($bulkData).' nomor tujuan.');
                } else {
                    MessageLog::whereIn('id', $logIds)->update(['status' => 'failed']);
                    $campaign->update(['status' => 'failed']);

                    return redirect()->route('campaigns')->with('error', 'Gagal mengirim kampanye. WhatsApp Gateway Error.');
                }
            }

            $campaign->update(['status' => 'completed']);

            return redirect()->route('campaigns')->with('success', 'Kampanye diperbarui (tidak ada nomor penerima yang ditemukan).');
        }

        return redirect()->route('campaigns')->with('success', 'Jadwal kampanye berhasil diperbarui.');
    }

    private function parseCsvToColumns($filePath)
    {
        $rows = [];
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return $rows;
        }

        $content = file_get_contents($filePath);
        $content = str_replace("\xEF\xBB\xBF", '', $content);

        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        $lines = explode("\n", $content);

        $delimiter = ',';
        if (count($lines) > 0) {
            $firstLine = $lines[0];
            $commas = substr_count($firstLine, ',');
            $semicolons = substr_count($firstLine, ';');
            $tabs = substr_count($firstLine, "\t");

            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line, $delimiter);

            $phoneNumber = trim($data[0] ?? '');
            if (empty($phoneNumber)) {
                continue;
            }

            $cleanedPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (str_starts_with($cleanedPhone, '08')) {
                $cleanedPhone = '628'.substr($cleanedPhone, 2);
            } elseif (str_starts_with($cleanedPhone, '8')) {
                $cleanedPhone = '628'.substr($cleanedPhone, 1);
            }

            if (strlen($cleanedPhone) < 9 || strlen($cleanedPhone) > 15) {
                continue;
            }

            $row = [];
            $cols = range('A', 'Z');

            foreach ($data as $index => $value) {
                if ($index >= 26) {
                    break;
                }

                $colName = $cols[$index];
                if ($index === 0) {
                    $row[$colName] = $cleanedPhone;
                } else {
                    $row[$colName] = trim($value);
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
