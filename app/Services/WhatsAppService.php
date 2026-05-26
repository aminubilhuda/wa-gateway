<?php

namespace App\Services;

use App\Models\Blacklist;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;

    protected $baseUrl;

    public function __construct()
    {
        $device = Device::first();
        $this->token = $device && $device->token ? $device->token : null;
        $this->baseUrl = $device && $device->gateway_url ? $device->gateway_url : env('WHATSAPP_GATEWAY_URL', 'http://localhost:3000');
    }

    protected function normalizePhone($number): string
    {
        $clean = preg_replace('/[^\d+]/', '', trim($number));

        if (str_starts_with($clean, '0')) {
            $clean = '62'.substr($clean, 1);
        } elseif (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '628'.substr($clean, 1);
        }

        return $clean;
    }

    protected function isBlacklisted($number): bool
    {
        $clean = $this->normalizePhone($number);

        return Blacklist::where('phone_number', $clean)->exists();
    }

    /**
     * Set dynamic token
     */
    public function setToken($token)
    {
        $this->token = $token;
        $device = Device::where('token', $token)->first();
        if ($device && $device->gateway_url) {
            $this->baseUrl = $device->gateway_url;
        }

        return $this;
    }

    /**
     * Get Device details/status from WhatsApp Gateway
     */
    public function getDeviceStatus()
    {
        if (! $this->token) {
            return ['status' => false, 'message' => 'Token tidak ditemukan.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post("{$this->baseUrl}/device");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp getDeviceStatus error: '.$e->getMessage());
        }

        return ['status' => false, 'message' => 'Gagal menghubungi WhatsApp Gateway.'];
    }

    /**
     * Get QR Code for device connection
     */
    public function getQr()
    {
        try {
            $response = Http::timeout(35)->withHeaders([
                'Authorization' => $this->token,
            ])->post("{$this->baseUrl}/qr", [
                'type' => 'qr',
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp getQr error: '.$e->getMessage());

            return ['status' => false, 'message' => 'Gagal menghubungi WhatsApp gateway.'];
        }
    }

    /**
     * Disconnect device
     */
    public function disconnect()
    {
        $response = Http::timeout(5)->withHeaders([
            'Authorization' => $this->token,
        ])->post("{$this->baseUrl}/disconnect");

        return $response->json();
    }

    /**
     * Send standard single message
     */
    public function sendMessage($target, $message, $url = null)
    {
        if ($this->isBlacklisted($target)) {
            Log::info("Blocked message to blacklisted number: {$target}");

            return ['status' => false, 'message' => 'Recipient is blacklisted'];
        }

        $payload = [
            'target' => $target,
            'message' => $message,
        ];

        if ($url) {
            $payload['url'] = $url;
        }

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => $this->token,
        ])->post("{$this->baseUrl}/send", $payload);

        return $response->json();
    }

    /**
     * Send bulk messages using 'data' parameter
     * $data format: [ ['target' => '...', 'message' => '...'], ... ]
     */
    public function sendBulkMessages(array $data)
    {
        $filtered = array_filter($data, function ($item) {
            $target = $item['target'] ?? null;
            if (! $target) {
                return false;
            }
            if ($this->isBlacklisted($target)) {
                Log::info("Blocked bulk message to blacklisted number: {$target}");

                return false;
            }

            return true;
        });

        if (empty($filtered)) {
            return ['status' => false, 'message' => 'All recipients are blacklisted or invalid'];
        }

        $jsonData = json_encode(array_values($filtered));

        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->post("{$this->baseUrl}/send", [
            'data' => $jsonData,
        ]);

        return $response->json();
    }
}
