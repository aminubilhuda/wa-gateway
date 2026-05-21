@extends('layouts.app')

@push('styles')
    <style>
        .qr-scanner-glow {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #25d366, transparent);
            animation: scan 3s linear infinite;
        }
        @keyframes scan {
            0% { top: 0%; }
            100% { top: 100%; }
        }
        .status-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto space-y-lg">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-xs text-on-surface-variant font-label-md text-label-md mb-lg">
            <a class="hover:text-primary" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-primary font-bold">Multi-Device Settings</span>
        </nav>

        <!-- Page Header -->
        <div class="flex justify-between items-start mb-xl">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-xs">WhatsApp Multi-Device Management</h2>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl">
                    Hubungkan beberapa akun WhatsApp untuk membagi beban pengiriman secara merata (*Round-Robin*) dan meminimalisir risiko blokir (*Anti-Banned*).
                </p>
            </div>
            <button onclick="toggleAddDeviceModal(true)" class="bg-primary text-on-primary px-lg py-sm rounded-lg font-bold hover:brightness-110 active:scale-95 transition-all flex items-center gap-xs shadow-md">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span>Tambah Perangkat</span>
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-lg py-md rounded-xl mb-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            
            <!-- Left Side: Devices List (col-span-7) -->
            <div class="lg:col-span-7 space-y-lg">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
                    <div class="px-lg py-md bg-surface-container-low border-b border-outline-variant">
                        <h4 class="font-headline-md text-headline-md text-on-surface">Daftar Perangkat WhatsApp</h4>
                    </div>
                    
                    <div class="divide-y divide-outline-variant">
                        @forelse($devices as $dev)
                            <div class="p-lg flex flex-col md:flex-row md:items-center justify-between gap-md hover:bg-surface-container/20 transition-colors">
                                <div class="space-y-xs">
                                    <div class="flex items-center gap-sm">
                                        <h5 class="font-bold text-body-lg text-on-surface">{{ $dev->name }}</h5>
                                        @if($dev->status == 'connected')
                                            <span class="px-sm py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-bold uppercase tracking-wider">Connected</span>
                                        @else
                                            <span class="px-sm py-0.5 bg-outline-variant text-on-surface-variant rounded-full text-xs font-bold uppercase tracking-wider">Disconnected</span>
                                        @endif
                                    </div>
                                    <p class="text-label-md text-on-surface-variant">Nomor: <span class="font-mono">{{ $dev->phone_number ?? '-' }}</span></p>
                                    <p class="text-label-md text-on-surface-variant">Jeda Kirim: <span class="bg-primary/5 text-primary px-sm py-0.5 rounded font-bold">{{ $dev->delay_seconds }} detik</span></p>
                                    <p class="text-label-md text-on-surface-variant">Gateway: <span class="bg-primary/5 text-[#006d2f] px-sm py-0.5 rounded font-mono text-xs">{{ $dev->gateway_url ?? 'https://api.fonnte.com' }}</span></p>
                                    <p class="text-label-sm text-tertiary truncate max-w-sm">Token: <span class="font-mono">{{ substr($dev->token, 0, 10) }}...</span></p>
                                </div>
                                
                                <div class="flex flex-wrap items-center gap-sm">
                                    @if($dev->status == 'connected')
                                        <form action="{{ route('settings.device.disconnect', $dev->id) }}" method="POST" onsubmit="return confirm('Putuskan koneksi perangkat ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-xs text-error hover:bg-error/10 rounded transition-colors flex items-center justify-center" title="Disconnect Device">
                                                <span class="material-symbols-outlined">link_off</span>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('settings', ['device_id' => $dev->id]) }}" class="px-sm py-xs bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-label-md font-bold transition-all flex items-center gap-xs">
                                            <span class="material-symbols-outlined text-[16px]">qr_code_scanner</span>
                                            <span>Scan QR</span>
                                        </a>
                                    @endif

                                    <a href="{{ route('settings', ['refresh_device_id' => $dev->id]) }}" class="p-xs text-secondary hover:bg-secondary/10 rounded transition-colors flex items-center justify-center" title="Refresh Status">
                                        <span class="material-symbols-outlined">sync</span>
                                    </a>

                                    <button onclick="openEditModal({{ \Illuminate\Support\Js::from($dev) }})" class="p-xs text-primary hover:bg-primary/10 rounded transition-colors flex items-center justify-center" title="Edit Device Config">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>

                                    <form action="{{ route('settings.device.delete', $dev->id) }}" method="POST" onsubmit="return confirm('Hapus perangkat ini secara permanen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-xs text-on-secondary-fixed-variant hover:text-error hover:bg-error/10 rounded transition-colors flex items-center justify-center" title="Delete Device">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-xl text-center text-on-surface-variant">Belum ada perangkat WhatsApp yang ditambahkan.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Webhook Info Card -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg space-y-md">
                    <h4 class="font-bold text-body-lg text-on-surface">Device Webhook Integration</h4>
                    <p class="text-body-sm text-on-surface-variant">Pastikan Anda menyalin URL Webhook berikut dan menempelkannya di dashboard Fonnte pada setiap perangkat yang terdaftar agar fitur status koneksi dan *auto-reply* berfungsi otomatis:</p>
                    
                    <div class="space-y-sm">
                        <div>
                            <label class="block text-label-sm text-outline uppercase tracking-wider mb-xs">Status Perangkat</label>
                            <div class="flex items-center gap-xs bg-surface-container-low p-sm rounded-lg border border-outline-variant/30">
                                <code class="flex-1 font-mono text-xs select-all text-on-surface truncate" id="deviceWebhookUrl">{{ url('/webhook/fonnte/device') }}</code>
                                <button onclick="copyToClipboard('deviceWebhookUrl', this)" type="button" class="p-xs hover:bg-surface-container rounded text-primary transition-all flex items-center" title="Copy to clipboard">
                                    <span class="material-symbols-outlined text-[18px]">content_copy</span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-label-sm text-outline uppercase tracking-wider mb-xs">Webhook Pesan Masuk / Auto-Reply</label>
                            <div class="flex items-center gap-xs bg-surface-container-low p-sm rounded-lg border border-outline-variant/30">
                                <code class="flex-1 font-mono text-xs select-all text-on-surface truncate" id="messageWebhookUrl">{{ url('/webhook/fonnte/message') }}</code>
                                <button onclick="copyToClipboard('messageWebhookUrl', this)" type="button" class="p-xs hover:bg-surface-container rounded text-primary transition-all flex items-center" title="Copy to clipboard">
                                    <span class="material-symbols-outlined text-[18px]">content_copy</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: QR Scanner (col-span-5) -->
            <div class="lg:col-span-5 space-y-lg">
                @if($selectedDevice)
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-xl flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-primary/5 pointer-events-none opacity-20"></div>
                        <div class="mb-md text-center">
                            <h4 class="font-bold text-body-lg text-on-surface">Scan QR: {{ $selectedDevice->name }}</h4>
                            <p class="text-label-md text-on-surface-variant">Lakukan pemindaian kode QR di bawah untuk menautkan WhatsApp Anda.</p>
                        </div>

                        <!-- QR Container -->
                        <div class="relative bg-white p-base border border-outline-variant rounded-lg shadow-sm mb-lg z-10">
                            <div class="qr-scanner-glow"></div>
                            @if($qrCode)
                                @php
                                    // Jika data URL (whatsapp-web.js) gunakan raw output, jika URL biasa (Fonnte) pakai langsung
                                    $isDataUrl = str_starts_with(trim($qrCode), 'data:');
                                @endphp
                                @if($isDataUrl)
                                    <img alt="WhatsApp Link QR Code" class="w-64 h-64" src="{!! trim($qrCode) !!}">
                                @else
                                    <img alt="WhatsApp Link QR Code" class="w-64 h-64" src="{{ $qrCode }}">
                                @endif
                            @else
                                @if(isset($qrResponse['reason']) && $qrResponse['reason'] == 'device already connect')
                                    <div class="w-64 h-64 flex flex-col items-center justify-center bg-green-50 text-center p-4">
                                        <span class="material-symbols-outlined text-[48px] mb-2 text-green-600">check_circle</span>
                                        <p class="font-bold text-green-700">Perangkat Sudah Terhubung</p>
                                        <p class="text-xs text-green-600 mt-1">Device ini sudah terhubung. Tidak perlu scan QR lagi.</p>
                                    </div>
                                @else
                                    <div class="w-64 h-64 flex flex-col items-center justify-center bg-surface-container-low text-on-surface-variant text-center p-4">
                                        <span class="material-symbols-outlined text-[48px] mb-2 text-error">error</span>
                                        <p class="font-bold text-error">QR Code Tidak Tersedia</p>
                                        <p class="text-xs">{{ $qrResponse['message'] ?? $qrResponse['reason'] ?? 'Gagal membuat koneksi QR code. Pastikan gateway berjalan dan token benar.' }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Status Indicator -->
                        <div class="flex items-center space-x-sm mb-lg">
                            <span class="w-3 h-3 bg-amber-500 rounded-full status-pulse"></span>
                            <span class="font-label-md text-label-md text-on-surface-variant tracking-wide uppercase">Waiting for scan</span>
                        </div>

                        <a href="{{ route('settings') }}" class="px-lg py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors active:scale-95 text-center w-full">
                            Selesai &amp; Tutup Scanner
                        </a>
                    </div>
                    
                    <!-- Connection Step Card -->
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg">
                        <h4 class="font-bold text-body-md text-on-surface mb-md">Cara Menghubungkan</h4>
                        <ol class="list-decimal pl-md text-label-md text-on-surface-variant space-y-xs">
                            <li>Buka aplikasi WhatsApp di ponsel Anda.</li>
                            <li>Ketuk **Menu** (Android) atau **Pengaturan** (iOS) &gt; **Linked Devices** / Perangkat Tertaut.</li>
                            <li>Ketuk **Tautkan Perangkat** lalu arahkan kamera ponsel ke QR Code di atas.</li>
                        </ol>
                    </div>
                @else
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-xl flex flex-col items-center justify-center text-center text-on-surface-variant min-h-[350px]">
                        <span class="material-symbols-outlined text-[64px] text-primary/30 mb-md">qr_code_scanner</span>
                        <h5 class="font-bold text-body-lg text-on-surface mb-xs">QR Code Scanner Pasif</h5>
                        <p class="text-label-md max-w-xs">Silakan pilih salah satu perangkat berstatus <strong>Disconnected</strong> di sebelah kiri dan klik <strong>Scan QR</strong> untuk menghubungkan akun WhatsApp Anda.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL: ADD DEVICE -->
    <div id="addDeviceModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-outline-variant">
            <div class="px-lg py-md bg-primary text-white flex justify-between items-center">
                <h4 class="font-bold text-body-lg">Tambah Perangkat WhatsApp Baru</h4>
                <button onclick="toggleAddDeviceModal(false)" class="text-white/80 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('settings.device.store') }}" method="POST" class="p-lg space-y-md">
                @csrf
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Nama Perangkat</label>
                    <input type="text" name="name" required placeholder="Contoh: CS Toko Utama / Admin 1"
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Fonnte API Token</label>
                    <input type="password" name="token" required placeholder="Masukkan Token Fonnte..."
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Gateway URL</label>
                    <input type="url" name="gateway_url" value="https://api.fonnte.com" placeholder="Contoh: https://api.fonnte.com atau http://localhost:3000"
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                    <p class="text-[11px] text-on-surface-variant mt-1">Gunakan <code>https://api.fonnte.com</code> untuk Fonnte, atau <code>http://localhost:3000</code> untuk self-host whatsapp-web.js. Kosongkan untuk menggunakan default.</p>
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Jeda Kirim / Delay Settings (detik)</label>
                    <select name="delay_seconds" class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                        <option value="1-3">1 - 3 Detik (Sangat Cepat)</option>
                        <option value="2-5" selected>2 - 5 Detik (Direkomendasikan - Aman)</option>
                        <option value="5-10">5 - 10 Detik (Lebih Lambat - Sangat Aman)</option>
                        <option value="10-20">10 - 20 Detik (Khusus Pesan Promosi Massal)</option>
                    </select>
                </div>
                <div class="pt-md flex justify-end gap-sm border-t border-outline-variant/30">
                    <button type="button" onclick="toggleAddDeviceModal(false)" class="px-md py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors">Batal</button>
                    <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-bold hover:brightness-105 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT DEVICE -->
    <div id="editDeviceModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-outline-variant">
            <div class="px-lg py-md bg-secondary text-white flex justify-between items-center">
                <h4 class="font-bold text-body-lg">Edit Perangkat WhatsApp</h4>
                <button onclick="toggleEditDeviceModal(false)" class="text-white/80 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="editDeviceForm" method="POST" class="p-lg space-y-md">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Nama Perangkat</label>
                    <input type="text" name="name" id="editDeviceName" required
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Fonnte API Token</label>
                    <input type="password" name="token" id="editDeviceToken" required
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Gateway URL</label>
                    <input type="url" name="gateway_url" id="editDeviceGatewayUrl" placeholder="Contoh: https://api.fonnte.com atau http://localhost:3000"
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                    <p class="text-[11px] text-on-surface-variant mt-1">Gunakan <code>https://api.fonnte.com</code> untuk Fonnte, atau <code>http://localhost:3000</code> untuk self-host whatsapp-web.js. Kosongkan untuk menggunakan default.</p>
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Jeda Kirim / Delay Settings (detik)</label>
                    <select name="delay_seconds" id="editDeviceDelay" class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                        <option value="1-3">1 - 3 Detik (Sangat Cepat)</option>
                        <option value="2-5">2 - 5 Detik (Direkomendasikan - Aman)</option>
                        <option value="5-10">5 - 10 Detik (Lebih Lambat - Sangat Aman)</option>
                        <option value="10-20">10 - 20 Detik (Khusus Pesan Promosi Massal)</option>
                    </select>
                </div>
                <div class="pt-md flex justify-end gap-sm border-t border-outline-variant/30">
                    <button type="button" onclick="toggleEditDeviceModal(false)" class="px-md py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors">Batal</button>
                    <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-bold hover:brightness-105 transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleAddDeviceModal(show) {
            const modal = document.getElementById('addDeviceModal');
            modal.classList.toggle('hidden', !show);
        }

        function toggleEditDeviceModal(show) {
            const modal = document.getElementById('editDeviceModal');
            modal.classList.toggle('hidden', !show);
        }

        function openEditModal(device) {
            document.getElementById('editDeviceName').value = device.name;
            document.getElementById('editDeviceToken').value = device.token;
            document.getElementById('editDeviceDelay').value = device.delay_seconds || '2-5';
            document.getElementById('editDeviceGatewayUrl').value = device.gateway_url || '';
            
            const form = document.getElementById('editDeviceForm');
            form.action = `/settings/device/${device.id}`;
            
            toggleEditDeviceModal(true);
        }

        function copyToClipboard(elementId, button) {
            const urlText = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(urlText).then(() => {
                const icon = button.querySelector('.material-symbols-outlined');
                const originalIcon = icon.textContent;
                icon.textContent = 'check';
                button.classList.add('text-green-500');
                setTimeout(() => {
                    icon.textContent = originalIcon;
                    button.classList.remove('text-green-500');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
@endpush
