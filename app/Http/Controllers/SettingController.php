<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\FonnteService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request, FonnteService $fonnte)
    {
        // Ensure at least one default device exists
        if (Device::count() === 0) {
            Device::create([
                'name' => 'Device Utama',
                'phone_number' => '-',
                'status' => 'disconnected',
                'delay_seconds' => '2-5',
                'token' => env('FONNTE_TOKEN') ?? '',
            ]);
        }

        // Sync status from Fonnte API only for the selected device (not all devices)
        // to avoid slow page loads when multiple devices exist
        $allDevices = Device::all();
        $syncTargetId = $request->filled('device_id') ? $request->device_id : ($request->filled('refresh_device_id') ? $request->refresh_device_id : null);

        if ($syncTargetId) {
            foreach ($allDevices as $dev) {
                if ($dev->id == $syncTargetId && $dev->token) {
                    $fonnte->setToken($dev->token);
                    $statusRes = $fonnte->getDeviceStatus();
                    if (isset($statusRes['status']) && $statusRes['status'] == true) {
                        $rawStatus = $statusRes['device_status'] ?? 'disconnect';
                        $status = ($rawStatus === 'connect' || $rawStatus === 'connected') ? 'connected' : 'disconnected';
                        $phoneNumber = $statusRes['device'] ?? $statusRes['sender'] ?? '-';
                        $dev->update([
                            'status' => $status,
                            'phone_number' => $phoneNumber,
                        ]);
                    }
                }
            }
        }

        $devices = Device::orderBy('created_at', 'desc')->get();

        $selectedDevice = null;
        $qrCode = null;
        $qrResponse = null;

        if ($request->filled('device_id')) {
            $selectedDevice = Device::find($request->device_id);
            if ($selectedDevice && $selectedDevice->token) {
                // Fetch QR code for specific device token
                $fonnte->setToken($selectedDevice->token);
                $qrResponse = $fonnte->getQr();

                if (isset($qrResponse['status']) && $qrResponse['status'] == true) {
                    $qrCode = $qrResponse['url'];
                    $selectedDevice->update(['status' => 'disconnected']);
                } elseif (isset($qrResponse['reason']) && $qrResponse['reason'] == 'device already connect') {
                    $selectedDevice->update(['status' => 'connected']);
                } else {
                    $selectedDevice->update(['status' => 'disconnected']);
                }
            }
        }

        return view('settings', compact('devices', 'selectedDevice', 'qrCode', 'qrResponse'));
    }

    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|max:255',
            'delay_seconds' => 'required|string|max:10',
            'gateway_url' => 'nullable|url|max:255',
        ]);

        Device::create([
            'name' => $validated['name'],
            'token' => $validated['token'],
            'delay_seconds' => $validated['delay_seconds'],
            'gateway_url' => $validated['gateway_url'] ?? 'https://api.fonnte.com',
            'phone_number' => '-',
            'status' => 'disconnected',
        ]);

        return redirect()->route('settings')->with('success', 'Perangkat baru berhasil ditambahkan.');
    }

    public function updateDevice(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|max:255',
            'delay_seconds' => 'required|string|max:10',
            'gateway_url' => 'nullable|url|max:255',
        ]);

        $validated['gateway_url'] = $validated['gateway_url'] ?? 'https://api.fonnte.com';

        $device->update($validated);

        return redirect()->route('settings')->with('success', 'Detail perangkat berhasil diperbarui.');
    }

    public function disconnectDevice(Device $device, FonnteService $fonnte)
    {
        if ($device->token) {
            $fonnte->setToken($device->token);
            try {
                $fonnte->disconnect();
            } catch (\Exception $e) {
                // Gateway might be offline — still allow local disconnect
                logger()->warning("Disconnect request to gateway failed: {$e->getMessage()}");
            }
        }

        $device->update(['status' => 'disconnected', 'phone_number' => '-']);

        return redirect()->route('settings')->with('success', 'Perangkat "'.$device->name.'" berhasil diputuskan.');
    }

    public function deleteDevice(Device $device)
    {
        $device->delete();

        return redirect()->route('settings')->with('success', 'Perangkat berhasil dihapus.');
    }
}
