@extends('layouts.app')

@push('styles')
    <style>
        .campaign-toggle-switch:checked + .campaign-toggle-slider {
            background-color: #22c55e;
        }
        .campaign-toggle-switch:checked + .campaign-toggle-slider:before {
            transform: translateX(1.25rem);
        }
    </style>
@endpush

@section('content')
    <form action="{{ route('campaigns.store') }}" method="POST" id="campaignForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">
    <!-- Page Header & Stepper -->
    <div class="flex flex-col gap-3 sm:gap-lg mb-3 sm:mb-xl">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-3 sm:gap-0">
            <div>
                <h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-background" id="campaignHeaderTitle">Create New Campaign</h2>
                <p class="text-on-surface-variant font-body-md text-sm sm:text-base">Broadcast high-impact messages to your audience in seconds.</p>
            </div>
            <div class="flex gap-2 sm:gap-md w-full sm:w-auto">
                <button type="button" id="cancelEditBtn" onclick="resetForm()" class="hidden flex-1 sm:flex-none px-3 sm:px-lg py-1.5 sm:py-sm border border-error text-error rounded-lg font-bold hover:bg-error/10 transition-colors active:scale-95 text-xs sm:text-sm">Batal Edit</button>
                <button type="button" id="previewBtn" onclick="openPreviewModal()" class="flex-1 sm:flex-none px-3 sm:px-lg py-1.5 sm:py-sm border border-outline-variant text-on-surface rounded-lg font-bold hover:bg-surface-container-low transition-colors active:scale-95 text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[16px] sm:text-[24px] align-middle">visibility</span>
                    Preview
                </button>
                <button type="submit" id="submitBtn" class="flex-1 sm:flex-none px-3 sm:px-lg py-1.5 sm:py-sm bg-primary-container text-on-primary-container rounded-lg font-bold hover:brightness-95 transition-all active:scale-95 flex items-center justify-center gap-1 sm:gap-sm text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[16px] sm:text-[24px]" id="submitIcon">send</span>
                    <span id="submitText">Send Now</span>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-lg py-2 sm:py-md rounded-xl text-xs sm:text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-lg py-2 sm:py-md rounded-xl text-xs sm:text-sm">
                {{ session('error') }}
            </div>
        @endif
        
        <!-- Wizard Stepper -->
        <div class="flex items-center w-full max-w-4xl mx-auto py-2 sm:py-md">
            <div class="flex-1 flex flex-col items-center gap-0.5 sm:gap-xs">
                <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-[11px] sm:text-base">1</div>
                <span class="text-[9px] sm:text-label-md text-primary text-center">Audience</span>
            </div>
            <div class="h-px bg-primary-container flex-1 mt-3 sm:mt-5"></div>
            <div class="flex-1 flex flex-col items-center gap-0.5 sm:gap-xs">
                <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-[11px] sm:text-base ring-2 sm:ring-4 ring-primary/20">2</div>
                <span class="text-[9px] sm:text-label-md font-bold text-on-surface text-center">Message</span>
            </div>
            <div class="h-px bg-outline-variant flex-1 mt-3 sm:mt-5"></div>
            <div class="flex-1 flex flex-col items-center gap-0.5 sm:gap-xs">
                <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center font-bold text-[11px] sm:text-base">3</div>
                <span class="text-[9px] sm:text-label-md text-on-surface-variant text-center">Schedule</span>
            </div>
        </div>
    </div>
    
    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-lg max-w-[1400px] mx-auto">
        <!-- Left Forms Area (col-span-7) -->
        <div class="lg:col-span-7 space-y-3 sm:space-y-lg campaign-card">
            
            <!-- Card 1: Choose Audience (No Tujuan) -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg space-y-3 sm:space-y-md">
                <div class="flex items-center gap-2 sm:gap-md border-b border-outline-variant/30 pb-2 sm:pb-sm">
                    <div class="p-1 sm:p-xs bg-primary/10 text-primary rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] sm:text-[24px]">group</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-[13px] sm:text-headline-sm text-on-surface">1. Choose Audience</h3>
                        <p class="text-[10px] sm:text-label-sm text-on-surface-variant">Tentukan penerima pesan kampanye Anda.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Sumber Penerima</label>
                    <select name="target_type" id="targetTypeSelect" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md" onchange="onTargetTypeChange()">
                        <option value="all">Semua Kontak Database</option>
                        <option value="manual">Input Manual (Tulis Nomor)</option>
                        <option value="group">Berdasarkan Group/Label Kontak</option>
                        <option value="random">Nomor Acak / Generator</option>
                        <option value="excel">Unggah Berkas Excel/CSV/TXT</option>
                    </select>
                </div>

                <!-- Sub-container: Semua Kontak -->
                <div id="allTargetContainer" class="p-sm bg-surface-container-low rounded-lg border border-outline-variant/30 text-body-md text-on-surface-variant/80">
                    <span class="material-symbols-outlined text-[16px] align-middle mr-xs text-primary">info</span>
                    Pesan akan otomatis dikirimkan ke <strong>seluruh kontak aktif</strong> yang ada di database.
                </div>

                <!-- Sub-container: Input Manual -->
                <div id="manualTargetContainer" class="hidden space-y-sm">
                    <label class="block text-label-md text-on-surface-variant">Masukkan Nomor WhatsApp</label>
                    <textarea name="manual_numbers" id="manualNumbersInput" class="w-full h-24 bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md placeholder:text-on-surface-variant/40" placeholder="Contoh: 62812345678,628987654321 (pisahkan dengan tanda koma)"></textarea>
                    <span class="text-label-sm text-on-surface-variant/60 block">Gunakan kode negara (misalnya: 62812xxx) tanpa tanda spasi, tanda + atau strip.</span>
                </div>

                <!-- Sub-container: Group/Label -->
                <div id="groupTargetContainer" class="hidden space-y-sm">
                    <label class="block text-label-md text-on-surface-variant">Pilih Label / Group</label>
                    <select name="group_label" id="groupLabelInput" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md">
                        <option value="">-- Pilih Group --</option>
                        @forelse($labels as $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @empty
                            <option value="" disabled>Belum ada label kontak di database</option>
                        @endforelse
                    </select>
                </div>

                <!-- Sub-container: Nomor Acak / Generator -->
                <div id="randomTargetContainer" class="hidden space-y-md">
                    <div class="flex gap-lg bg-surface-container-low p-sm rounded-lg border border-outline-variant/30">
                        <label class="flex items-center gap-xs font-label-md text-on-surface cursor-pointer">
                            <input type="radio" name="random_source" value="db" checked onchange="onRandomSourceChange()">
                            <span>Acak dari Database</span>
                        </label>
                        <label class="flex items-center gap-xs font-label-md text-on-surface cursor-pointer">
                            <input type="radio" name="random_source" value="generate" onchange="onRandomSourceChange()">
                            <span>Generate Nomor Baru</span>
                        </label>
                    </div>

                    <!-- Acak dari Database -->
                    <div id="randomDbContainer" class="space-y-sm">
                        <label class="block text-label-md text-on-surface-variant">Jumlah Kontak Acak</label>
                        <input type="number" name="random_db_count" id="randomDbCountInput" min="1" value="5" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md">
                        <span class="text-label-sm text-on-surface-variant/60 block">Sistem akan memilih kontak secara acak dari database Anda.</span>
                    </div>

                    <!-- Generate Nomor Baru -->
                    <div id="randomGenContainer" class="hidden space-y-sm grid grid-cols-2 gap-md">
                        <div class="col-span-1">
                            <label class="block text-label-md text-on-surface-variant mb-xs">Prefix Nomor</label>
                            <input type="text" name="random_gen_prefix" id="randomGenPrefixInput" value="62812" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-label-md text-on-surface-variant mb-xs">Jumlah Nomor</label>
                            <input type="number" name="random_gen_count" id="randomGenCountInput" min="1" value="10" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md">
                        </div>
                        <span class="col-span-2 text-label-sm text-on-surface-variant/60 block">Akan men-generate nomor secara acak dengan awalan prefix yang Anda tentukan.</span>
                    </div>
                </div>

                <!-- Sub-container: Excel -->
                <div id="excelTargetContainer" class="hidden space-y-md">
                    <div class="space-y-xs">
                        <label class="block text-label-md text-on-surface-variant font-bold">Unggah Berkas Excel/CSV</label>
                        <input type="file" name="excel_file" id="excelFileInput" accept=".csv,.txt" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md">
                    </div>
                    <div class="bg-primary/5 border border-primary/10 rounded-lg p-sm space-y-sm">
                        <p class="text-label-sm text-primary font-bold flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[18px]">info</span>
                            Panduan Customisasi Pesan Dinamis (Excel/CSV):
                        </p>
                        <ul class="list-disc pl-md space-y-xs text-label-sm text-on-surface-variant/80">
                            <li>Ekspor tabel Excel Anda ke format <strong>CSV (Comma Delimited)</strong> sebelum mengunggahnya.</li>
                            <li>Kolom pertama (<strong>Kolom A</strong>) harus berisi nomor WhatsApp penerima (misal: 081234567890).</li>
                            <li>Anda dapat menggunakan data kolom lainnya dengan menuliskan variabel <strong><code class="bg-primary/10 text-primary px-1 rounded font-mono font-bold"><?php echo '{{A}}'; ?></code> s/d <code class="bg-primary/10 text-primary px-1 rounded font-mono font-bold"><?php echo '{{Z}}'; ?></code></strong> (huruf kapital) di isi pesan Anda.</li>
                            <li>Contoh: <em>"Halo <?php echo '{{B}}'; ?>, invoice Anda sebesar <?php echo '{{C}}'; ?> sudah jatuh tempo."</em></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Card 2: Compose Message (Isi Pesan & Lampiran) -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg">
                <div class="flex items-center gap-2 sm:gap-md border-b border-outline-variant/30 pb-2 sm:pb-sm mb-2 sm:mb-md">
                    <div class="p-1 sm:p-xs bg-primary/10 text-primary rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] sm:text-[24px]">chat</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-[13px] sm:text-headline-sm text-on-surface">2. Compose Message</h3>
                        <p class="text-[10px] sm:text-label-sm text-on-surface-variant">Tulis isi pesan dan unggah lampiran media jika perlu.</p>
                    </div>
                </div>

                <!-- Campaign / Schedule Name -->
                <div class="mb-3 sm:mb-4">
                    <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Nama Jadwal / Kampanye</label>
                    <input type="text" name="name" required class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md" placeholder="e.g. Jadwal Kirim Promo Bulanan">
                </div>

                <!-- Choose Saved Template -->
                <div class="mb-3 sm:mb-4">
                    <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Gunakan Templat (Opsional)</label>
                    <select id="templateSelector" onchange="applyTemplate()" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md">
                        <option value="">-- Pilih Templat Pesan --</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->message_body }}">{{ $tpl->title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Editor Tools -->
                <div class="flex flex-wrap gap-1 sm:gap-sm mb-2 sm:mb-md p-1.5 sm:p-sm bg-surface-container-low rounded-lg border border-outline-variant/30">
                    <button type="button" class="px-1.5 sm:px-sm py-0.5 sm:py-1 bg-surface-container-lowest border border-outline-variant rounded text-[10px] sm:text-label-sm hover:border-primary transition-colors" onclick="insertVar('[Name]')">[Name]</button>
                    <button type="button" class="px-1.5 sm:px-sm py-0.5 sm:py-1 bg-surface-container-lowest border border-outline-variant rounded text-[10px] sm:text-label-sm hover:border-primary transition-colors" onclick="insertVar('[Order_ID]')">[Order_ID]</button>
                    <button type="button" class="px-1.5 sm:px-sm py-0.5 sm:py-1 bg-surface-container-lowest border border-outline-variant rounded text-[10px] sm:text-label-sm hover:border-primary transition-colors" onclick="insertVar('[Discount]')">[Discount]</button>
                </div>

                <!-- Textarea -->
                <textarea name="message_template" class="w-full h-32 sm:h-48 bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary font-body-md text-xs sm:text-sm placeholder:text-on-surface-variant/40" id="messageEditor" placeholder="Tulis pesan WhatsApp Anda di sini..." required></textarea>

                <!-- Attachment Area -->
                <div class="mt-3 sm:mt-lg pt-3 sm:pt-lg border-t border-outline-variant">
                    <div class="flex items-center justify-between mb-1 sm:mb-sm">
                        <span class="text-[10px] sm:text-label-md text-on-surface-variant">Lampiran Media</span>
                        <span class="text-[9px] sm:text-label-sm text-on-surface-variant/60">Maks 16MB</span>
                    </div>
                    
                    <div onclick="document.getElementById('attachmentInput').click()" class="border-2 border-dashed border-outline-variant rounded-xl p-3 sm:p-md flex flex-col items-center justify-center gap-1 sm:gap-xs hover:bg-surface-container-low transition-colors cursor-pointer group">
                        <input type="file" name="attachment" id="attachmentInput" class="hidden" onchange="handleAttachmentChange(this)">
                        <span class="material-symbols-outlined text-outline group-hover:text-primary transition-colors text-[24px] sm:text-[36px]">cloud_upload</span>
                        <p class="text-xs sm:text-body-md text-on-surface-variant text-center">Klik untuk mengunggah Lampiran</p>
                        <span class="text-[9px] sm:text-label-xs text-on-surface-variant/50 text-center">Gambar, Video, Suara, atau Dokumen</span>
                    </div>

                    <div id="attachmentPreviewContainer" class="hidden mt-1 sm:mt-sm p-1.5 sm:p-sm bg-primary/10 rounded-lg flex items-center justify-between text-xs sm:text-body-md text-primary font-medium">
                        <div class="flex items-center gap-1 sm:gap-xs truncate">
                            <span class="material-symbols-outlined text-[14px] sm:text-[18px]">draft</span>
                            <span id="attachmentPreviewText" class="truncate">nama_file.jpg</span>
                        </div>
                        <button type="button" class="text-error hover:text-error-hover flex-shrink-0" onclick="clearAttachment()">
                            <span class="material-symbols-outlined text-[14px] sm:text-[18px]">close</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card 3: Scheduling Options (Jadwal Pengiriman) -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg space-y-3 sm:space-y-md">
                <div class="flex items-center gap-2 sm:gap-md border-b border-outline-variant/30 pb-2 sm:pb-sm">
                    <div class="p-1 sm:p-xs bg-primary/10 text-primary rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] sm:text-[24px]">schedule</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-[13px] sm:text-headline-sm text-on-surface">3. Schedule</h3>
                        <p class="text-[10px] sm:text-label-sm text-on-surface-variant">Tentukan tipe pengiriman terjadwal.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-xs sm:text-sm text-on-surface">Jadwalkan pengiriman?</p>
                        <p class="text-[10px] sm:text-label-sm text-on-surface-variant">Kirim otomatis di lain waktu.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox" name="is_scheduled" id="isScheduledCheckbox" class="sr-only peer" onchange="toggleScheduleInput()">
                        <div class="w-9 sm:w-11 h-5 sm:h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] sm:after:top-[2px] after:left-[1px] sm:after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-4 sm:after:h-5 after:w-4 sm:after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>

                <div id="scheduleTimeContainer" class="hidden pt-2 sm:pt-md border-t border-outline-variant/30 space-y-2 sm:space-y-md">
                    <div>
                        <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Jadwal Pengiriman</label>
                        <select name="schedule_type" id="scheduleTypeSelect" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md" onchange="onScheduleTypeChange()">
                            <option value="once">Sekali Kirim (One-Time)</option>
                            <option value="daily">Kirim Setiap Hari (Daily)</option>
                            <option value="weekly">Kirim Setiap Minggu (Weekly)</option>
                            <option value="monthly">Kirim Setiap Bulan (Monthly)</option>
                            <option value="minute">Kirim Setiap Beberapa Menit</option>
                            <option value="hour">Kirim Setiap Beberapa Jam</option>
                        </select>
                    </div>

                    <div id="onceScheduleContainer" class="space-y-1 sm:space-y-sm">
                        <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Tanggal &amp; Waktu (WIB)</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduledAtInput" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md">
                    </div>

                    <div id="weeklyScheduleContainer" class="hidden space-y-1 sm:space-y-sm">
                        <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Pilih Hari (Setiap Minggu)</label>
                        <select name="scheduled_day_of_week" id="scheduledDayOfWeekInput" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md">
                            <option value="1">Senin</option>
                            <option value="2">Selasa</option>
                            <option value="3">Rabu</option>
                            <option value="4">Kamis</option>
                            <option value="5">Jumat</option>
                            <option value="6">Sabtu</option>
                            <option value="7">Minggu</option>
                        </select>
                    </div>

                    <div id="monthlyScheduleContainer" class="hidden space-y-1 sm:space-y-sm">
                        <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Pilih Tanggal (1 - 31)</label>
                        <select name="scheduled_day_of_month" id="scheduledDayOfMonthInput" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md">
                            @for($i = 1; $i <= 31; $i++)
                                <option value="{{ $i }}">Tanggal {{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div id="timeScheduleContainer" class="hidden space-y-1 sm:space-y-sm">
                        <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs">Jam Pengiriman (WIB)</label>
                        <input type="time" name="scheduled_time" id="scheduledTimeInput" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md">
                    </div>

                    <div id="intervalScheduleContainer" class="hidden space-y-1 sm:space-y-sm">
                        <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1 sm:mb-xs" id="intervalValueLabel">Durasi Interval</label>
                        <input type="number" name="interval_value" id="intervalValueInput" min="1" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-1.5 sm:p-sm focus:ring-primary focus:border-primary text-xs sm:text-body-md" placeholder="e.g. 15">
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-1 sm:pt-sm border-t border-outline-variant/30">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-xs sm:text-sm text-on-surface">Aktifkan Jadwal?</p>
                            <p class="text-[10px] sm:text-label-sm text-on-surface-variant">Diproses otomatis oleh scheduler.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                            <input type="checkbox" name="is_active" id="isActiveCheckbox" class="sr-only peer" checked>
                            <div class="w-9 sm:w-11 h-5 sm:h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] sm:after:top-[2px] after:left-[1px] sm:after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-4 sm:after:h-5 after:w-4 sm:after:w-5 after:transition-all peer-checked:bg-[#22c55e]"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Live Preview Area -->
        <div class="lg:col-span-5 campaign-card">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg h-full">
                <div class="flex items-center gap-1 sm:gap-sm mb-2 sm:mb-lg border-b border-outline-variant pb-2 sm:pb-md">
                    <span class="material-symbols-outlined text-primary text-[18px] sm:text-[24px]">visibility</span>
                    <h3 class="font-headline-md text-headline-md text-sm sm:text-base text-on-surface">Live Preview</h3>
                </div>
                <!-- WhatsApp Simulation -->
                <div class="bg-[#e5ddd5] rounded-2xl overflow-hidden shadow-inner border border-black/5 aspect-[3/4] sm:aspect-[9/16] max-h-[500px] lg:max-h-[600px] flex flex-col mx-auto max-w-[240px] sm:max-w-[320px]">
                    <!-- Chat Header -->
                    <div class="bg-[#075e54] p-sm flex items-center gap-sm">
                        <span class="material-symbols-outlined text-white text-[20px]">arrow_back</span>
                        <div class="w-8 h-8 rounded-full bg-slate-300 overflow-hidden">
                            <img alt="Preview User" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD6Wp4zaqtETKJi3FoobxRpl5MMFN4jvrurkqAdcVgGexToa7tYzwKtRR2rVaElB4bOLcxLMvualN-EhycLU9b6bFyf7JjbU-1E3HJxXRFg43HTujnbCZA_G__WD-fW3osg1e9pXZqzr9XUVZmoFx382-QcijykioABpnwOf4tctsxYvFjjJVJiIojPPKSEhwdL-vvQ3QtdDgS8KH9iBAwvnetDZgplPNZym6WsOIWHa3wtFDfon5P-dOCP_aH4cfgf97It7aK-ScHZ">
                        </div>
                        <div class="flex-1">
                            <p class="text-white text-xs font-bold leading-none">Customer Name</p>
                            <p class="text-white/70 text-[10px]">online</p>
                        </div>
                        <span class="material-symbols-outlined text-white text-[20px]">more_vert</span>
                    </div>
                    <!-- Chat Body -->
                    <div class="flex-1 p-md flex flex-col justify-end custom-scrollbar overflow-y-auto" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAOS3QUW47DsJFw-jEZhfYXDqrPNkDSJE061FV02daS_93F2lOdCYJGtFXa0c0VOGY3Bze4GtQZ9BT4-YTHfDVDDKzHVfwmFKn51ffK_UVY7xfOyDGSZ46eJkxHnx1J_70mVs4tAKs0Kx0ImKlwvYt5FF6lq86SkrO8bY2v2D6hprSOPPEVw_nDDRLSiGNl_pM3mNaKxPVjypsi-mAixRINU4an8RTE0Yn-PLCK435QSKJP0Aj_jBBTz8Hplm6Whulaw2nOiY6t7WP9'); background-size: cover; background-opacity: 0.1;">
                        <!-- Date Stamp -->
                        <div class="self-center bg-[#d1e9f9] text-[#1e293b] text-[10px] px-sm py-0.5 rounded-md mb-md uppercase font-bold tracking-wider">Today</div>
                        <!-- Preview Bubble -->
                        <div class="whatsapp-bubble self-end max-w-[85%] p-sm mb-xs shadow-sm">
                            <div class="text-sm text-[#303030] whitespace-pre-wrap" id="previewContent">Hi [Name]! Thanks for your order #[Order_ID]. We've prepared a special [Discount] off for your next visit! 🌿</div>
                            <div class="flex justify-end gap-1 items-center mt-1">
                                <span class="text-[9px] text-gray-500">14:02</span>
                                <span class="material-symbols-outlined text-[14px] text-blue-400" style="font-variation-settings: 'FILL' 1;">done_all</span>
                            </div>
                        </div>
                    </div>
                    <!-- Chat Input Simulation -->
                    <div class="p-xs bg-[#f0f0f0] flex items-center gap-xs">
                        <div class="flex-1 bg-white rounded-full px-sm py-1 flex items-center gap-xs">
                            <span class="material-symbols-outlined text-gray-400 text-[18px]">mood</span>
                            <span class="text-[12px] text-gray-400 flex-1">Type a message</span>
                            <span class="material-symbols-outlined text-gray-400 text-[18px]">attach_file</span>
                            <span class="material-symbols-outlined text-gray-400 text-[18px]">photo_camera</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#075e54] flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-[18px]" style="font-variation-settings: 'FILL' 1;">mic</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>

    <!-- Campaigns List Section -->
    <div class="max-w-[1400px] mx-auto mt-3 sm:mt-xl">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="p-3 sm:p-lg border-b border-outline-variant bg-surface-container-low flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <h3 class="font-headline-md text-headline-md text-sm sm:text-base text-on-surface">Campaign History</h3>
                    <p class="text-[11px] sm:text-body-sm text-on-surface-variant">Track your sent and upcoming scheduled broadcasts.</p>
                </div>
                <div class="flex items-center gap-1 sm:gap-xs">
                    <span class="w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-[10px] sm:text-label-md text-on-surface-variant font-bold">WIB</span>
                </div>
                <a href="{{ route('campaigns.archived') }}" class="text-[10px] sm:text-label-md text-outline hover:text-primary font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px] sm:text-[16px]">archive</span>
                    Archived
                </a>
            </div>
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[10px] sm:text-label-sm text-outline border-b border-outline-variant">
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider">Name</th>
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider">Message</th>
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider">Schedule</th>
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider">Aktif</th>
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider">Created</th>
                            <th class="px-2 sm:px-lg py-2 sm:py-4 font-semibold uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($campaigns as $campaign)
                        <tr class="hover:bg-background/50 transition-colors" id="campaign-row-{{ $campaign->id }}">
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md text-[11px] sm:text-label-md text-on-surface font-semibold">{{ $campaign->name }}</td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md text-[11px] sm:text-body-md text-on-surface-variant/80 max-w-[80px] sm:max-w-xs truncate" title="{{ $campaign->message_template }}">{{ $campaign->message_template }}</td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md whitespace-nowrap text-[11px] sm:text-body-md text-on-surface">
                                @if($campaign->schedule_type === 'once' && $campaign->scheduled_at)
                                    <div class="flex items-center gap-0.5 sm:gap-xs text-primary font-medium">
                                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">calendar_month</span>
                                        <span>{{ \Carbon\Carbon::parse($campaign->scheduled_at)->format('d M Y H:i') }}</span>
                                    </div>
                                @elseif($campaign->schedule_type === 'daily' && $campaign->scheduled_time)
                                    <div class="flex items-center gap-0.5 sm:gap-xs text-indigo-600 font-medium">
                                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">today</span>
                                        <span>Daily {{ $campaign->scheduled_time }}</span>
                                    </div>
                                @elseif($campaign->schedule_type === 'weekly' && $campaign->scheduled_time)
                                    @php $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu']; @endphp
                                    <div class="flex items-center gap-0.5 sm:gap-xs text-purple-600 font-medium">
                                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">calendar_view_week</span>
                                        <span>{{ $days[$campaign->scheduled_day_of_week] ?? '' }} {{ $campaign->scheduled_time }}</span>
                                    </div>
                                @elseif($campaign->schedule_type === 'monthly' && $campaign->scheduled_time)
                                    <div class="flex items-center gap-0.5 sm:gap-xs text-pink-600 font-medium">
                                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">calendar_month</span>
                                        <span>Tgl {{ $campaign->scheduled_day_of_month }}</span>
                                    </div>
                                @elseif($campaign->schedule_type === 'minute' && $campaign->interval_value)
                                    <div class="flex items-center gap-0.5 sm:gap-xs text-teal-600 font-medium">
                                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">timelapse</span>
                                        <span>Setiap {{ $campaign->interval_value }} mnt</span>
                                    </div>
                                @elseif($campaign->schedule_type === 'hour' && $campaign->interval_value)
                                    <div class="flex items-center gap-0.5 sm:gap-xs text-orange-600 font-medium">
                                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">hourglass_empty</span>
                                        <span>Setiap {{ $campaign->interval_value }} jam</span>
                                    </div>
                                @else
                                    <span class="text-outline/70">Sent Now</span>
                                @endif
                            </td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                                @if($campaign->status === 'scheduled')
                                    <span class="inline-flex items-center gap-1 px-1.5 sm:px-3 py-0.5 sm:py-1 bg-amber-100 text-amber-800 rounded-full text-[9px] sm:text-label-sm font-bold whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[12px] sm:text-[16px] animate-pulse">schedule</span>
                                        Scheduled
                                    </span>
                                @elseif($campaign->status === 'running')
                                    <span class="inline-flex items-center gap-1 px-1.5 sm:px-3 py-0.5 sm:py-1 bg-blue-100 text-blue-800 rounded-full text-[9px] sm:text-label-sm font-bold whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[12px] sm:text-[16px] animate-spin">sync</span>
                                        Running
                                    </span>
                                @elseif($campaign->status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-1.5 sm:px-3 py-0.5 sm:py-1 bg-green-100 text-green-800 rounded-full text-[9px] sm:text-label-sm font-bold whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[12px] sm:text-[16px]">check_circle</span>
                                        Done
                                    </span>
                                @elseif($campaign->status === 'failed')
                                    <span class="inline-flex items-center gap-1 px-1.5 sm:px-3 py-0.5 sm:py-1 bg-red-100 text-red-800 rounded-full text-[9px] sm:text-label-sm font-bold whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[12px] sm:text-[16px]">error</span>
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 sm:px-3 py-0.5 sm:py-1 bg-gray-100 text-gray-800 rounded-full text-[9px] sm:text-label-sm font-bold whitespace-nowrap">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input class="sr-only campaign-toggle-switch" type="checkbox" data-id="{{ $campaign->id }}" {{ $campaign->is_active ? 'checked' : '' }}/>
                                    <div class="w-7 sm:w-10 h-3.5 sm:h-5 bg-outline-variant rounded-full transition-all campaign-toggle-slider relative before:content-[''] before:absolute before:top-[1px] sm:before:top-[2px] before:left-[1px] sm:before:left-[2px] before:bg-white before:rounded-full before:h-2.5 sm:before:h-4 before:w-2.5 sm:before:w-4 before:transition-all"></div>
                                </label>
                            </td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md whitespace-nowrap text-[10px] sm:text-label-sm text-outline">{{ $campaign->created_at->format('d M') }}</td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md text-right">
                                <div class="flex justify-end items-center gap-0.5 sm:gap-xs">
                                    <button type="button" onclick="editCampaign({{ json_encode($campaign) }})" class="p-0.5 sm:p-xs text-primary hover:bg-primary/10 rounded transition-colors" title="Edit Schedule">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[20px]">edit</span>
                                    </button>
                                    <form action="{{ route('campaigns.destroy', $campaign->id) }}" method="POST" class="delete-campaign-form inline" data-id="{{ $campaign->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-0.5 sm:p-xs text-outline hover:text-error hover:bg-error-container/20 rounded transition-colors" title="Delete Schedule">
                                            <span class="material-symbols-outlined text-[16px] sm:text-[20px]">delete</span>
                                        </button>
                                    </form>
                                    @if($campaign->archived_at)
                                    <button type="button" onclick="restoreCampaign({{ $campaign->id }})" class="p-0.5 sm:p-xs text-outline hover:text-green-600 hover:bg-green-50 rounded transition-colors" title="Restore">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[20px]">unarchive</span>
                                    </button>
                                    @else
                                    <button type="button" onclick="archiveCampaign({{ $campaign->id }})" class="p-0.5 sm:p-xs text-outline hover:text-amber-600 hover:bg-amber-50 rounded transition-colors" title="Archive">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[20px]">archive</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-2 sm:px-lg py-1.5 sm:py-md text-center text-on-surface-variant text-xs">Belum ada kampanye.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($campaigns->hasPages())
            <div class="p-2 sm:p-md bg-surface-container-low border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-[10px] sm:text-label-md text-on-surface-variant">Showing {{ $campaigns->firstItem() ?? 0 }} to {{ $campaigns->lastItem() ?? 0 }} of {{ $campaigns->total() }} campaigns</span>
                <div class="flex items-center gap-1 sm:space-x-xs">
                    @if ($campaigns->onFirstPage())
                        <button class="p-0.5 sm:p-xs border border-outline-variant rounded-md text-outline opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_left</span>
                        </button>
                    @else
                        <a href="{{ $campaigns->previousPageUrl() }}" class="p-0.5 sm:p-xs border border-outline-variant rounded-md hover:bg-white text-outline flex items-center">
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_left</span>
                        </a>
                    @endif
                    @foreach ($campaigns->getUrlRange(max(1, $campaigns->currentPage() - 2), min($campaigns->lastPage(), $campaigns->currentPage() + 2)) as $page => $url)
                        @if ($page == $campaigns->currentPage())
                            <button class="w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center bg-primary text-on-primary rounded-md font-bold text-[10px] sm:text-label-md">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center border border-outline-variant rounded-md hover:bg-white text-on-surface text-[10px] sm:text-label-md">{{ $page }}</a>
                        @endif
                    @endforeach
                    @if ($campaigns->hasMorePages())
                        <a href="{{ $campaigns->nextPageUrl() }}" class="p-0.5 sm:p-xs border border-outline-variant rounded-md hover:bg-white text-outline flex items-center">
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_right</span>
                        </a>
                    @else
                        <button class="p-0.5 sm:p-xs border border-outline-variant rounded-md text-outline opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_right</span>
                        </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
<div id="fullPreviewModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-base">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Campaign Preview</h3>
            <button onclick="document.getElementById('fullPreviewModal').classList.add('hidden')" class="text-on-surface-variant hover:text-error">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-lg space-y-md">
            <div class="bg-surface-container-low rounded-lg p-md">
                <p class="text-label-sm text-on-surface-variant mb-xs">Recipients:</p>
                <p id="previewRecipientCount" class="font-bold text-body-md text-on-surface">-</p>
            </div>
            <div>
                <p class="text-label-sm text-on-surface-variant mb-xs">Message:</p>
                <div id="previewMessageContent" class="bg-surface-container-low p-md rounded-lg text-body-md text-on-surface whitespace-pre-wrap border border-outline-variant/30"></div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-md text-xs text-amber-800">
                <strong>Note:</strong> Variables like <code>[Name]</code> will be replaced with actual contact data when sent.
            </div>
        </div>
        <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end">
            <button onclick="document.getElementById('fullPreviewModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        // Target Type visibility toggling
        function onTargetTypeChange() {
            const targetType = document.getElementById('targetTypeSelect').value;
            
            // Hide all sub-containers
            document.getElementById('allTargetContainer').classList.add('hidden');
            document.getElementById('manualTargetContainer').classList.add('hidden');
            document.getElementById('groupTargetContainer').classList.add('hidden');
            document.getElementById('randomTargetContainer').classList.add('hidden');
            document.getElementById('excelTargetContainer').classList.add('hidden');
            
            // Reset required flags
            document.getElementById('manualNumbersInput').required = false;
            document.getElementById('groupLabelInput').required = false;
            document.getElementById('excelFileInput').required = false;
            
            if (targetType === 'all') {
                document.getElementById('allTargetContainer').classList.remove('hidden');
            } else if (targetType === 'manual') {
                document.getElementById('manualTargetContainer').classList.remove('hidden');
                document.getElementById('manualNumbersInput').required = true;
            } else if (targetType === 'group') {
                document.getElementById('groupTargetContainer').classList.remove('hidden');
                document.getElementById('groupLabelInput').required = true;
            } else if (targetType === 'random') {
                document.getElementById('randomTargetContainer').classList.remove('hidden');
                onRandomSourceChange();
            } else if (targetType === 'excel') {
                document.getElementById('excelTargetContainer').classList.remove('hidden');
                document.getElementById('excelFileInput').required = true;
            }
        }

        // Random Source visibility toggling
        function onRandomSourceChange() {
            const source = document.querySelector('input[name="random_source"]:checked').value;
            const dbContainer = document.getElementById('randomDbContainer');
            const genContainer = document.getElementById('randomGenContainer');
            
            dbContainer.classList.add('hidden');
            genContainer.classList.add('hidden');
            
            document.getElementById('randomDbCountInput').required = false;
            document.getElementById('randomGenPrefixInput').required = false;
            document.getElementById('randomGenCountInput').required = false;
            
            if (source === 'db') {
                dbContainer.classList.remove('hidden');
                document.getElementById('randomDbCountInput').required = true;
            } else {
                genContainer.classList.remove('hidden');
                document.getElementById('randomGenPrefixInput').required = true;
                document.getElementById('randomGenCountInput').required = true;
            }
        }

        // File Attachment handling
        function handleAttachmentChange(input) {
            const file = input.files[0];
            const previewContainer = document.getElementById('attachmentPreviewContainer');
            const previewText = document.getElementById('attachmentPreviewText');
            if (file) {
                previewText.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                previewContainer.classList.remove('hidden');
            } else {
                previewContainer.classList.add('hidden');
            }
        }

        function clearAttachment() {
            const input = document.getElementById('attachmentInput');
            input.value = '';
            document.getElementById('attachmentPreviewContainer').classList.add('hidden');
        }

        // Schedule Type visibility toggling
        function onScheduleTypeChange() {
            const type = document.getElementById('scheduleTypeSelect').value;
            const onceContainer = document.getElementById('onceScheduleContainer');
            const weeklyContainer = document.getElementById('weeklyScheduleContainer');
            const monthlyContainer = document.getElementById('monthlyScheduleContainer');
            const timeContainer = document.getElementById('timeScheduleContainer');
            const intervalContainer = document.getElementById('intervalScheduleContainer');
            
            const onceInput = document.getElementById('scheduledAtInput');
            const timeInput = document.getElementById('scheduledTimeInput');
            const intervalInput = document.getElementById('intervalValueInput');
            const intervalLabel = document.getElementById('intervalValueLabel');

            // Reset visibility
            onceContainer.classList.add('hidden');
            weeklyContainer.classList.add('hidden');
            monthlyContainer.classList.add('hidden');
            timeContainer.classList.add('hidden');
            intervalContainer.classList.add('hidden');

            // Reset requirements
            onceInput.required = false;
            timeInput.required = false;
            intervalInput.required = false;

            if (type === 'once') {
                onceContainer.classList.remove('hidden');
                onceInput.required = true;
            } else if (type === 'daily') {
                timeContainer.classList.remove('hidden');
                timeInput.required = true;
            } else if (type === 'weekly') {
                weeklyContainer.classList.remove('hidden');
                timeContainer.classList.remove('hidden');
                timeInput.required = true;
            } else if (type === 'monthly') {
                monthlyContainer.classList.remove('hidden');
                timeContainer.classList.remove('hidden');
                timeInput.required = true;
            } else if (type === 'minute') {
                intervalContainer.classList.remove('hidden');
                intervalLabel.textContent = 'Durasi Interval (Menit)';
                intervalInput.placeholder = 'e.g. 15 (kirim setiap 15 menit)';
                intervalInput.required = true;
            } else if (type === 'hour') {
                intervalContainer.classList.remove('hidden');
                intervalLabel.textContent = 'Durasi Interval (Jam)';
                intervalInput.placeholder = 'e.g. 2 (kirim setiap 2 jam)';
                intervalInput.required = true;
            }
        }

        function toggleScheduleInput() {
            const checkbox = document.getElementById('isScheduledCheckbox');
            const container = document.getElementById('scheduleTimeContainer');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitIcon = document.getElementById('submitIcon');
            
            if (checkbox.checked) {
                container.classList.remove('hidden');
                onScheduleTypeChange();
                if (submitText) submitText.textContent = 'Schedule Campaign';
                if (submitIcon) submitIcon.textContent = 'calendar_month';
            } else {
                container.classList.add('hidden');
                document.getElementById('scheduledAtInput').required = false;
                document.getElementById('scheduledTimeInput').required = false;
                document.getElementById('intervalValueInput').required = false;
                if (submitText) submitText.textContent = 'Send Now';
                if (submitIcon) submitIcon.textContent = 'send';
            }
        }

        const editor = document.getElementById('messageEditor');

        function applyTemplate() {
            const selector = document.getElementById('templateSelector');
            if (selector && selector.value && editor) {
                editor.value = selector.value;
                editor.dispatchEvent(new Event('input'));
            }
        }

        const preview = document.getElementById('previewContent');

        // Sync editor with preview
        if (editor && preview) {
            editor.addEventListener('input', (e) => {
                const val = e.target.value;
                preview.textContent = val || "Hi [Name]! Thanks for your order #[Order_ID]. We've prepared a special [Discount] off for your next visit! 🌿";
                
                // Highlight variables in preview if needed
                const htmlVal = preview.innerHTML;
                const highlighted = htmlVal.replace(/(\[.*?\])/g, '<span class="font-bold text-primary">$1</span>');
                preview.innerHTML = highlighted;
            });
        }

        // Function to insert variable at cursor
        function insertVar(variable) {
            if (!editor) return;
            const start = editor.selectionStart;
            const end = editor.selectionEnd;
            const text = editor.value;
            const before = text.substring(0, start);
            const after = text.substring(end, text.length);
            editor.value = before + variable + after;
            editor.focus();
            editor.dispatchEvent(new Event('input'));
        }

        // Function to load campaign details into form for editing
        function editCampaign(campaign) {
            // Scroll to form smoothly
            document.getElementById('campaignForm').scrollIntoView({ behavior: 'smooth' });

            // Set Form action & method
            const form = document.getElementById('campaignForm');
            form.action = `/campaigns/${campaign.id}`;
            document.getElementById('formMethod').value = 'PUT';

            // Set Header Title & Buttons
            document.getElementById('campaignHeaderTitle').textContent = `Edit Campaign: ${campaign.name}`;
            document.getElementById('cancelEditBtn').classList.remove('hidden');

            // Populate Main Fields
            document.getElementsByName('name')[0].value = campaign.name;
            document.getElementById('messageEditor').value = campaign.message_template;
            document.getElementById('messageEditor').dispatchEvent(new Event('input')); // trigger preview update

            // Target Type
            const targetSelect = document.getElementById('targetTypeSelect');
            targetSelect.value = campaign.target_type;
            onTargetTypeChange();

            // Populate specific target fields
            if (campaign.target_type === 'manual') {
                document.getElementById('manualNumbersInput').value = campaign.target_value || '';
            } else if (campaign.target_type === 'group') {
                const groupSelect = document.getElementById('groupLabelInput');
                if (groupSelect) groupSelect.value = campaign.target_value || '';
            } else if (campaign.target_type === 'random') {
                document.getElementById('randomDbCountInput').value = campaign.target_value || 5;
            }

            // Schedule Options
            const isScheduled = campaign.schedule_type !== 'once' || campaign.scheduled_at !== null || campaign.status === 'scheduled';
            const isScheduledCheckbox = document.getElementById('isScheduledCheckbox');
            isScheduledCheckbox.checked = isScheduled;
            toggleScheduleInput();

            if (isScheduled) {
                const typeSelect = document.getElementById('scheduleTypeSelect');
                typeSelect.value = campaign.schedule_type;
                onScheduleTypeChange();

                if (campaign.schedule_type === 'once' && campaign.scheduled_at) {
                    try {
                        const dt = new Date(campaign.scheduled_at);
                        const tzOffset = dt.getTimezoneOffset() * 60000;
                        const localISOTime = (new Date(dt.getTime() - tzOffset)).toISOString().slice(0, 16);
                        document.getElementById('scheduledAtInput').value = localISOTime;
                    } catch (e) {
                        console.error('Date parsing error', e);
                    }
                } else if (campaign.schedule_type === 'daily') {
                    document.getElementById('scheduledTimeInput').value = campaign.scheduled_time || '08:00';
                } else if (campaign.schedule_type === 'weekly') {
                    document.getElementById('scheduledTimeInput').value = campaign.scheduled_time || '08:00';
                    document.getElementById('scheduledDayOfWeekInput').value = campaign.scheduled_day_of_week || 1;
                } else if (campaign.schedule_type === 'monthly') {
                    document.getElementById('scheduledTimeInput').value = campaign.scheduled_time || '08:00';
                    document.getElementById('scheduledDayOfMonthInput').value = campaign.scheduled_day_of_month || 1;
                } else if (campaign.schedule_type === 'minute' || campaign.schedule_type === 'hour') {
                    document.getElementById('intervalValueInput').value = campaign.interval_value || 1;
                }

                // Active Switch
                document.getElementById('isActiveCheckbox').checked = campaign.is_active === 1 || campaign.is_active === true;
            }
        }

        function openPreviewModal() {
            const message = document.getElementById('messageEditor').value;
            const targetType = document.getElementById('targetTypeSelect').value;
            let count = 0;
            if (targetType === 'all') count = 'All active contacts';
            else if (targetType === 'manual') {
                const nums = document.getElementById('manualNumbersInput').value.split(',').filter(n => n.trim());
                count = nums.length + ' numbers';
            }
            else if (targetType === 'group') count = 'Selected group';
            else if (targetType === 'random') count = 'Random contacts';
            else if (targetType === 'excel') count = 'Excel/CSV rows';
            document.getElementById('previewRecipientCount').textContent = count;
            document.getElementById('previewMessageContent').textContent = message || '(empty message)';
            document.getElementById('fullPreviewModal').classList.remove('hidden');
        }

        // Function to reset form back to Create mode
        function resetForm() {
            const form = document.getElementById('campaignForm');
            form.action = "{{ route('campaigns.store') }}";
            document.getElementById('formMethod').value = 'POST';

            // Reset Title & Buttons
            document.getElementById('campaignHeaderTitle').textContent = "Create New Campaign";
            document.getElementById('cancelEditBtn').classList.add('hidden');

            // Reset inputs
            form.reset();
            
            // Dispatch triggers
            document.getElementById('messageEditor').dispatchEvent(new Event('input'));
            onTargetTypeChange();
            toggleScheduleInput();
        }

        // Simple entrance animation
        window.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.campaign-card');
            cards.forEach((card, i) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, i * 150);
            });
            
            // Trigger target type change on page load
            onTargetTypeChange();

            // Toggle campaign active status via Ajax
            document.querySelectorAll('.campaign-toggle-switch').forEach(toggle => {
                // Set opacity for inactive row initially
                const campaignId = toggle.getAttribute('data-id');
                const row = document.getElementById(`campaign-row-${campaignId}`);
                if (!toggle.checked && row) {
                    row.classList.add('opacity-60');
                }

                toggle.addEventListener('change', function() {
                    const isChecked = this.checked;
                    if (isChecked) {
                        if (row) row.classList.remove('opacity-60');
                    } else {
                        if (row) row.classList.add('opacity-60');
                    }

                    fetch(`/campaigns/${campaignId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        console.log('Status updated', data);
                    })
                    .catch(err => console.error('Error toggling campaign schedule', err));
                });
            });

            // Handle AJAX deletion for campaigns/schedules
            document.querySelectorAll('.delete-campaign-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!confirm('Apakah Anda yakin ingin menghapus jadwal/kampanye ini?')) {
                        return;
                    }
                    
                    const campaignId = this.getAttribute('data-id');
                    const url = this.getAttribute('action');
                    const row = document.getElementById(`campaign-row-${campaignId}`);
                    
                    if (row) {
                        row.style.transition = 'all 0.5s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => {
                            row.remove();
                            // Check if table is empty, display empty message
                            const tbody = document.querySelector('table tbody');
                            if (tbody && tbody.querySelectorAll('tr[id^="campaign-row-"]').length === 0) {
                                tbody.innerHTML = `
                                    <tr>
                                        <td colspan="8" class="px-lg py-md text-center text-on-surface-variant">Belum ada kampanye yang dibuat.</td>
                                    </tr>
                                `;
                            }
                        }, 500);
                    }

                    fetch(url, {
                        method: 'POST',
                        body: new FormData(this),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message || 'Kampanye berhasil dihapus.');
                        } else {
                            alert('Gagal menghapus kampanye.');
                        }
                    })
                    .catch(err => {
                        console.error('Error deleting campaign', err);
                        alert('Terjadi kesalahan saat menghapus kampanye.');
                    });
                });
            });
        });

        function archiveCampaign(id) {
            if (!confirm('Arsipkan kampanye ini?')) return;
            const row = document.getElementById(`campaign-row-${id}`);
            fetch(`/campaigns/${id}/archive`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    row.style.transition = 'all 0.4s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(-20px)';
                    setTimeout(() => { row.remove(); }, 400);
                }
            });
        }

        function restoreCampaign(id) {
            if (!confirm('Kembalikan kampanye ini?')) return;
            const row = document.getElementById(`campaign-row-${id}`);
            fetch(`/campaigns/${id}/restore`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    row.style.transition = 'all 0.4s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(-20px)';
                    setTimeout(() => { row.remove(); }, 400);
                }
            });
        }
    </script>
@endpush
