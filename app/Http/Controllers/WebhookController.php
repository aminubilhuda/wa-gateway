<?php

namespace App\Http\Controllers;

use App\Models\AutoReply;
use App\Models\Blacklist;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MenuSession;
use App\Models\MessageLog;
use App\Models\WebhookLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private function verifySignature(Request $request): bool
    {
        $secret = config('whatsapp.webhook_secret');
        if (! $secret) {
            return true;
        }

        $signature = $request->header('X-Webhook-Signature');
        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Handle device status webhook
     */
    public function device(Request $request)
    {
        WebhookLog::create([
            'type' => 'device',
            'payload' => json_encode($request->all()),
            'status' => 'received',
        ]);

        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'active',
                'message' => 'WhatsApp Device Webhook is online and ready for GET/POST requests.',
            ]);
        }

        if (! $this->verifySignature($request)) {
            Log::warning('Webhook device rejected: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
        }

        $status = $request->input('status');
        $token = $request->input('token');

        Log::info('WhatsApp Device Webhook received', ['status' => $status, 'token' => $token]);

        $device = null;
        if ($token) {
            $device = Device::where('token', $token)->first();
        }
        if (! $device) {
            $device = Device::first();
        }

        if ($device) {
            if ($status == 'connect' || $status == 'connected') {
                $device->update(['status' => 'connected']);
            } elseif ($status == 'disconnect' || $status == 'disconnected') {
                $device->update(['status' => 'disconnected']);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle incoming messages for Auto Reply
     */
    public function message(Request $request, WhatsAppService $whatsapp)
    {
        WebhookLog::create([
            'type' => 'message',
            'payload' => json_encode($request->all()),
            'status' => 'received',
        ]);

        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'active',
                'message' => 'WhatsApp Message Webhook is online and ready for GET/POST requests.',
            ]);
        }

        if (! $this->verifySignature($request)) {
            Log::warning('Webhook message rejected: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
        }

        $sender = $request->input('sender');
        $messageText = strtolower($request->input('message'));
        $token = $request->input('token');
        $isLid = $request->boolean('is_lid');
        $rawSender = $request->input('raw_sender');
        $normalizedSender = $request->input('normalized_sender');

        Log::info('WhatsApp Message Webhook received', [
            'sender' => $sender,
            'raw_sender' => $rawSender,
            'normalized_sender' => $normalizedSender,
            'message' => $messageText,
            'token' => $token ? substr($token, 0, 8).'...' : 'none',
            'is_lid' => $isLid,
        ]);

        if ($token) {
            $whatsapp->setToken($token);
        }

        if (! $sender) {
            return response()->json(['success' => false, 'message' => 'Sender parameter is required.']);
        }

        // Normalize phone number - strip non-digits first
        $cleanNumber = preg_replace('/[^0-9]/', '', $sender);

        // Handle edge cases for LID numbers or malformed input
        if (empty($cleanNumber) || strlen($cleanNumber) < 5) {
            Log::warning('Invalid sender number received', ['sender' => $sender, 'raw_sender' => $rawSender]);

            return response()->json(['success' => false, 'message' => 'Invalid sender number.']);
        }

        // Normalize to Indonesian format
        if (str_starts_with($cleanNumber, '0')) {
            $cleanNumber = '62'.substr($cleanNumber, 1);
        } elseif (! str_starts_with($cleanNumber, '62')) {
            $cleanNumber = '62'.$cleanNumber;
        }

        $contact = Contact::firstOrCreate(
            ['phone_number' => $cleanNumber],
            ['name' => 'Auto Saved ('.substr($cleanNumber, -4).')', 'label' => 'Incoming']
        );

        // Build reply target: prefer raw sender JID if available (handles @lid properly)
        if ($rawSender && str_contains($rawSender, '@')) {
            $replyTarget = $rawSender;
        } elseif ($isLid) {
            $replyTarget = "{$sender}@lid";
        } else {
            $replyTarget = "{$sender}@c.us";
        }

        if ($messageText === 'stop' || $messageText === 'unsubscribe') {
            Blacklist::firstOrCreate(
                ['phone_number' => $cleanNumber],
                ['reason' => 'Unsubscribed via Webhook ('.strtoupper($messageText).')']
            );

            $replyText = 'Anda telah berhasil keluar dari daftar penerima pesan kami. Anda tidak akan menerima pesan blast lagi.';
            $response = $whatsapp->sendMessage($replyTarget, $replyText);
            $status = (isset($response['status']) && $response['status'] == true) ? 'sent' : 'failed';

            MessageLog::create([
                'campaign_id' => null,
                'contact_id' => $contact->id,
                'message_body' => $replyText,
                'status' => $status,
                'sent_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Unsubscribed and blacklisted successfully.']);
        }

        if ($messageText === 'back' || $messageText === 'menu' || $messageText === 'kembali') {
            $activeSession = MenuSession::getActiveSession($sender);

            if ($activeSession) {
                $currentRule = $activeSession->autoReply;
                if ($currentRule && $currentRule->parent_id) {
                    MenuSession::createOrUpdateSession($sender, $currentRule->parent_id);
                    $parentRule = AutoReply::find($currentRule->parent_id);
                    if ($parentRule) {
                        $children = $parentRule->children()->where('is_active', true)->get();
                        $replyText = $parentRule->response_message."\n\nKetik *back* untuk kembali ke menu sebelumnya.";
                        $whatsapp->sendMessage($replyTarget, $replyText);
                    }
                } else {
                    MenuSession::where('phone_number', $sender)->delete();
                    $mainMenus = AutoReply::whereNull('parent_id')->where('is_active', true)->get();
                    if ($mainMenus->isNotEmpty()) {
                        $replyText = "Menu utama:\n";
                        foreach ($mainMenus as $menu) {
                            $replyText .= "{$menu->keyword} - {$menu->response_message}\n";
                        }
                        $whatsapp->sendMessage($replyTarget, $replyText);
                    }
                }
            } else {
                $mainMenus = AutoReply::whereNull('parent_id')->where('is_active', true)->get();
                if ($mainMenus->isNotEmpty()) {
                    $replyText = "Menu utama:\n";
                    foreach ($mainMenus as $menu) {
                        $replyText .= "{$menu->keyword} - {$menu->response_message}\n";
                    }
                    $whatsapp->sendMessage($replyTarget, $replyText);
                }
            }

            return response()->json(['success' => true, 'message' => 'Returned to previous menu.']);
        }

        $activeSession = MenuSession::getActiveSession($sender);

        if ($activeSession) {
            $parentId = $activeSession->auto_reply_id;
            $rules = AutoReply::where('parent_id', $parentId)->where('is_active', true)->get();

            foreach ($rules as $rule) {
                $keyword = strtolower($rule->keyword);
                $match = false;

                if ($rule->match_type === 'exact' && $messageText === $keyword) {
                    $match = true;
                } elseif ($rule->match_type === 'contains' && strpos($messageText, $keyword) !== false) {
                    $match = true;
                }

                if ($match) {
                    $attachmentUrl = $rule->attachment_path ?? null;
                    $grandchildren = $rule->children()->where('is_active', true)->get();

                    if ($grandchildren->isNotEmpty()) {
                        MenuSession::createOrUpdateSession($sender, $rule->id);
                    } else {
                        MenuSession::where('phone_number', $sender)->delete();
                    }

                    $responseMessage = $rule->response_message;
                    if ($grandchildren->isNotEmpty()) {
                        $responseMessage .= "\n\nKetik *back* untuk kembali ke menu sebelumnya.";
                    } else {
                        $responseMessage .= "\n\nKetik *back* atau *menu* untuk kembali ke menu utama.";
                    }
                    $response = $whatsapp->sendMessage($replyTarget, $responseMessage, $attachmentUrl);
                    $status = 'failed';
                    if (isset($response['status']) && $response['status'] == true) {
                        $status = 'sent';
                    }

                    MessageLog::create([
                        'campaign_id' => null,
                        'contact_id' => $contact->id,
                        'message_body' => $rule->response_message,
                        'status' => $status,
                        'sent_at' => now(),
                    ]);

                    $rule->increment('trigger_count');

                    Log::info('Auto Reply Sent (Child Menu)', [
                        'to' => $replyTarget,
                        'parent_id' => $parentId,
                        'keyword' => $keyword,
                        'reply' => $rule->response_message,
                    ]);

                    return response()->json(['success' => true]);
                }
            }

            $parentRule = $activeSession->autoReply;
            if ($parentRule) {
                $children = AutoReply::where('parent_id', $parentRule->id)->where('is_active', true)->get();
                if ($children->isNotEmpty()) {
                    $replyText = $parentRule->response_message."\n\nKetik *back* untuk kembali ke menu sebelumnya.";
                    $whatsapp->sendMessage($replyTarget, $replyText);
                }
            }

            return response()->json(['success' => true, 'message' => 'No child match found.']);
        }

        $allRules = AutoReply::where('is_active', true)->orderBy('parent_id')->orderBy('id')->get();

        foreach ($allRules as $rule) {
            $keyword = strtolower($rule->keyword);
            $match = false;

            if ($rule->match_type === 'exact' && $messageText === $keyword) {
                $match = true;
            } elseif ($rule->match_type === 'contains' && strpos($messageText, $keyword) !== false) {
                $match = true;
            }

            if ($match) {
                $children = $rule->children()->where('is_active', true)->get();

                if ($children->isNotEmpty()) {
                    MenuSession::createOrUpdateSession($sender, $rule->id);

                    $whatsapp->sendMessage($replyTarget, $rule->response_message."\n\nKetik *back* untuk kembali ke menu sebelumnya.");

                    $rule->increment('trigger_count');

                    Log::info('Auto Reply Parent Menu Triggered', [
                        'to' => $replyTarget,
                        'keyword' => $keyword,
                        'children_count' => $children->count(),
                    ]);

                    return response()->json(['success' => true]);
                }

                $attachmentUrl = $rule->attachment_path ?? null;
                $response = $whatsapp->sendMessage($replyTarget, $rule->response_message, $attachmentUrl);
                $status = 'failed';
                if (isset($response['status']) && $response['status'] == true) {
                    $status = 'sent';
                }

                MessageLog::create([
                    'campaign_id' => null,
                    'contact_id' => $contact->id,
                    'message_body' => $rule->response_message,
                    'status' => $status,
                    'sent_at' => now(),
                ]);

                $rule->increment('trigger_count');

                Log::info('Auto Reply Sent', [
                    'to' => $replyTarget,
                    'keyword' => $keyword,
                    'reply' => $rule->response_message,
                    'attachment' => $attachmentUrl ? 'yes' : 'no',
                ]);

                break;
            }
        }

        return response()->json(['success' => true]);
    }
}
