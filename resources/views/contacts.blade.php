@extends('layouts.app')

@section('content')
    <!-- Header Actions Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-md mb-3 sm:mb-lg">
        <div>
            <h3 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-background">Contact Management</h3>
            <p class="text-body-md text-body-md text-on-surface-variant">Manage and segment your audience for targeted messaging.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:space-x-sm">
            <button onclick="window.location.href='{{ route('contacts.export', request()->all()) }}'" class="px-2 sm:px-md py-1.5 sm:py-sm bg-surface-container-lowest border border-outline-variant text-secondary font-bold rounded-lg flex items-center gap-1 sm:space-x-xs hover:bg-surface-container-low active:scale-95 transition-all text-xs sm:text-sm">
                <span class="material-symbols-outlined text-[16px] sm:text-[20px]">download</span>
                <span class="hidden xs:inline">Export CSV</span>
            </button>
            <button onclick="window.location.href='{{ route('contacts.duplicates') }}'" class="px-2 sm:px-md py-1.5 sm:py-sm bg-surface-container-lowest border border-outline-variant text-secondary font-bold rounded-lg flex items-center gap-1 sm:space-x-xs hover:bg-amber-50 hover:text-amber-700 active:scale-95 transition-all text-xs sm:text-sm">
                <span class="material-symbols-outlined text-[16px] sm:text-[20px]">merge</span>
                <span class="hidden xs:inline">Duplicates</span>
            </button>
            <button onclick="document.getElementById('importContactsModal').classList.remove('hidden')" class="px-2 sm:px-md py-1.5 sm:py-sm bg-surface-container-lowest border border-outline-variant text-secondary font-bold rounded-lg flex items-center gap-1 sm:space-x-xs hover:bg-surface-container-low active:scale-95 transition-all text-xs sm:text-sm">
                <span class="material-symbols-outlined text-[16px] sm:text-[20px]">upload_file</span>
                <span class="hidden xs:inline">Import</span>
            </button>
            <button onclick="document.getElementById('addContactModal').classList.remove('hidden')" class="px-2 sm:px-md py-1.5 sm:py-sm bg-primary text-on-primary font-bold rounded-lg flex items-center gap-1 sm:space-x-xs hover:bg-on-primary-fixed-variant active:scale-95 transition-all shadow-sm text-xs sm:text-sm">
                <span class="material-symbols-outlined text-[16px] sm:text-[20px]">person_add</span>
                <span class="hidden xs:inline">Add Contact</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-lg py-md rounded-xl mb-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-lg py-md rounded-xl mb-lg">
            {{ session('error') }}
        </div>
    @endif
    
    <!-- Search & Filter Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-md mb-3 sm:mb-md shadow-sm">
        <form action="{{ route('contacts') }}" method="GET" class="flex flex-col sm:flex-row gap-2 sm:gap-sm items-stretch sm:items-center w-full">
            <div class="relative flex-1 w-full">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input class="w-full pl-10 pr-4 py-1.5 sm:py-2 bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md text-sm" placeholder="Cari nama, nomor, atau grup..." name="search" value="{{ request('search') }}" type="text" onchange="this.form.submit()"/>
            </div>
            <div class="w-full sm:w-48 lg:w-64">
                <select name="label" onchange="this.form.submit()" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-1.5 sm:py-2 outline-none focus:ring-2 focus:ring-primary/20 text-body-md text-sm cursor-pointer">
                    <option value="">Semua Grup/Label</option>
                    @foreach($segments as $seg)
                        <option value="{{ $seg->label }}" {{ request('label') == $seg->label ? 'selected' : '' }}>{{ $seg->label }} ({{ $seg->total }})</option>
                    @endforeach
                </select>
            </div>
            @if(request()->filled('search') || request()->filled('label'))
                <a href="{{ route('contacts') }}" class="px-3 sm:px-md py-1.5 sm:py-sm bg-secondary-container/30 border border-secondary-container text-on-secondary-container font-label-md rounded-lg hover:bg-secondary-container/50 transition-all flex items-center justify-center gap-xs text-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-[16px]">refresh</span>
                    <span>Reset</span>
                </a>
            @endif
        </form>
    </div>
    
    <!-- Bulk Action & Filter Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-2 sm:p-md mb-3 sm:mb-md flex flex-wrap items-center justify-between gap-2 opacity-0 translate-y-2 pointer-events-none transition-all duration-300" id="bulkActions">
        <div class="flex flex-wrap items-center gap-2 sm:space-x-lg">
            <span class="text-xs sm:text-body-md font-bold text-primary"><span id="selectedCount">0</span> Selected</span>
            <div class="h-4 sm:h-6 w-px bg-outline-variant hidden sm:block"></div>
            <div class="flex items-center gap-1 sm:space-x-sm">
                <button onclick="bulkDeleteContacts()" class="px-1.5 sm:px-sm py-0.5 sm:py-xs text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-md transition-colors flex items-center gap-0.5 sm:space-x-xs">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">delete</span>
                    <span class="text-[10px] sm:text-label-md">Delete</span>
                </button>
                <button onclick="document.getElementById('bulkGroupModal').classList.remove('hidden')" class="px-1.5 sm:px-sm py-0.5 sm:py-xs text-on-surface-variant hover:text-primary hover:bg-primary-container/10 rounded-md transition-colors flex items-center gap-0.5 sm:space-x-xs">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">label</span>
                    <span class="text-[10px] sm:text-label-md hidden xs:inline">Group</span>
                </button>
                <button onclick="document.getElementById('bulkBroadcastModal').classList.remove('hidden')" class="px-1.5 sm:px-sm py-0.5 sm:py-xs text-on-surface-variant hover:text-primary hover:bg-primary-container/10 rounded-md transition-colors flex items-center gap-0.5 sm:space-x-xs">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">chat</span>
                    <span class="text-[10px] sm:text-label-md hidden xs:inline">Broadcast</span>
                </button>
            </div>
        </div>
        <button class="text-[10px] sm:text-label-md text-on-surface-variant hover:underline" onclick="deselectAll()">Clear</button>
    </div>
    
    <!-- Bento Grid Contacts Section -->
    <div class="grid grid-cols-1 gap-3 sm:gap-lg">
        <!-- Main Data Table Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full text-left border-collapse min-w-[650px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant">
                        <tr>
                            <th class="p-2 sm:p-md w-10 sm:w-12 text-center">
                                <input class="w-3.5 h-3.5 sm:w-4 sm:h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" id="selectAll" type="checkbox"/>
                            </th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Name</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Phone</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Tags</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Status</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Added</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md font-bold text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($contacts as $contact)
                        <tr class="hover:bg-surface-container transition-colors group" id="contact-row-{{ $contact->id }}">
                            <td class="p-2 sm:p-md text-center">
                                <input class="contact-checkbox w-3.5 h-3.5 sm:w-4 sm:h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" type="checkbox" value="{{ $contact->id }}"/>
                            </td>
                            <td class="p-2 sm:p-md">
                                <div class="flex items-center gap-1.5 sm:space-x-md">
                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-secondary-container flex items-center justify-center font-bold text-on-secondary-container text-[9px] sm:text-[12px] flex-shrink-0">{{ strtoupper(substr($contact->name, 0, 2)) }}</div>
                                    <span class="text-xs sm:text-body-md font-bold text-on-surface truncate max-w-[80px] sm:max-w-none">{{ $contact->name }}</span>
                                </div>
                            </td>
                            <td class="p-2 sm:p-md text-xs sm:text-body-md text-on-surface-variant whitespace-nowrap">{{ $contact->phone_number }}</td>
                            <td class="p-2 sm:p-md">
                                <span class="px-1 sm:px-xs py-[1px] sm:py-[2px] bg-primary-container text-on-primary-container text-[9px] sm:text-label-sm rounded-full font-bold">{{ $contact->label ?? 'Unlabeled' }}</span>
                            </td>
                            <td class="p-2 sm:p-md">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input class="sr-only contact-toggle-active" type="checkbox" data-id="{{ $contact->id }}" {{ $contact->is_active ? 'checked' : '' }}/>
                                    <div class="w-8 sm:w-10 h-4 sm:h-5 bg-outline-variant rounded-full transition-all before:content-[''] before:absolute before:top-[1px] sm:before:top-[2px] before:left-[1px] sm:before:left-[2px] before:bg-white before:rounded-full before:h-3 sm:before:h-4 before:w-3 sm:before:w-4 before:transition-all {{ $contact->is_active ? 'bg-green-500 before:translate-x-4 sm:before:translate-x-5' : '' }}"></div>
                                </label>
                            </td>
                            <td class="p-2 sm:p-md">
                                <div class="flex items-center text-on-surface-variant gap-1 sm:space-x-xs">
                                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-primary-container"></div>
                                    <span class="text-[10px] sm:text-body-md whitespace-nowrap">{{ $contact->created_at->diffForHumans() }}</span>
                                </div>
                            </td>
                            <td class="p-2 sm:p-md text-right">
                                <div class="flex justify-end items-center gap-0.5 sm:gap-xs">
                                    <button type="button" onclick="openEditModal({{ $contact->id }}, {{ \Illuminate\Support\Js::from($contact->name) }}, {{ \Illuminate\Support\Js::from($contact->phone_number) }}, {{ \Illuminate\Support\Js::from($contact->label ?? '') }}, {{ $contact->is_active ? 'true' : 'false' }})" class="text-on-surface-variant hover:text-primary transition-colors p-0.5 sm:p-0" title="Edit Contact">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[24px]">edit</span>
                                    </button>
                                    <button type="button" onclick="openScheduleModal({{ $contact->id }}, '{{ addslashes($contact->name) }}', '{{ $contact->phone_number }}')" class="text-on-surface-variant hover:text-primary transition-colors p-0.5 sm:p-0" title="Schedule Message">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[24px]">calendar_clock</span>
                                    </button>
                                    <button type="button" onclick="deleteContact(event, {{ $contact->id }})" class="text-on-surface-variant hover:text-error transition-colors p-0.5 sm:p-0" title="Delete Contact">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[24px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-2 sm:p-md text-center text-on-surface-variant text-xs sm:text-sm">Belum ada kontak.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($duplicates) && $duplicates->count() > 0)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 sm:p-md mb-3">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-amber-600">warning</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-sm text-amber-800">Duplikat Ditemukan!</h4>
                        <p class="text-xs text-amber-700 mb-2">{{ $duplicates->count() }} nomor terduplikasi.</p>
                        <div class="space-y-1">
                            @foreach($duplicates as $dup)
                            <div class="flex items-center justify-between bg-white p-2 rounded border border-amber-100">
                                <span class="text-xs font-mono">{{ $dup->phone_number }} ({{ $dup->count }}x - {{ $dup->names }})</span>
                                <button onclick="mergeDuplicates('{{ $dup->phone_number }}')" class="text-xs bg-amber-500 text-white px-2 py-1 rounded hover:bg-amber-600">Merge</button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Pagination Footer -->
            <div class="p-2 sm:p-md bg-surface-container-low border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-[10px] sm:text-label-md text-on-surface-variant">Showing {{ $contacts->firstItem() ?? 0 }} to {{ $contacts->lastItem() ?? 0 }} of {{ $contacts->total() }} contacts</span>
                @if ($contacts->hasPages())
                <div class="flex items-center gap-1 sm:space-x-xs">
                    @if ($contacts->onFirstPage())
                        <button class="p-1 sm:p-xs border border-outline-variant rounded-md text-outline opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_left</span>
                        </button>
                    @else
                        <a href="{{ $contacts->previousPageUrl() }}" class="p-1 sm:p-xs border border-outline-variant rounded-md hover:bg-white text-outline flex items-center">
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_left</span>
                        </a>
                    @endif
                    @foreach ($contacts->getUrlRange(max(1, $contacts->currentPage() - 2), min($contacts->lastPage(), $contacts->currentPage() + 2)) as $page => $url)
                        @if ($page == $contacts->currentPage())
                            <button class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center bg-primary text-on-primary rounded-md font-bold text-[11px] sm:text-label-md">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center border border-outline-variant rounded-md hover:bg-white text-on-surface text-[11px] sm:text-label-md">{{ $page }}</a>
                        @endif
                    @endforeach
                    @if ($contacts->hasMorePages())
                        <a href="{{ $contacts->nextPageUrl() }}" class="p-1 sm:p-xs border border-outline-variant rounded-md hover:bg-white text-outline flex items-center">
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_right</span>
                        </a>
                    @else
                        <button class="p-1 sm:p-xs border border-outline-variant rounded-md text-outline opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[16px] sm:text-[24px]">chevron_right</span>
                        </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
        
        <!-- Bottom Asymmetric Cards (Bento Style) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-lg">
            <div class="md:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg relative overflow-hidden group shadow-sm">
                <div class="absolute -right-8 -bottom-8 opacity-10 group-hover:scale-110 transition-transform duration-500">
                    <span class="material-symbols-outlined text-[80px] sm:text-[120px] text-primary" style="font-variation-settings: 'FILL' 1;">pie_chart</span>
                </div>
                <h4 class="font-headline-md text-headline-md text-sm sm:text-base mb-2 sm:mb-md">Segmentation</h4>
                <ul class="space-y-1 sm:space-y-sm relative z-10">
                    @forelse($segments->take(5) as $seg)
                    <li class="flex items-center justify-between">
                        <span class="text-xs sm:text-body-md text-on-surface-variant">{{ $seg->label }}</span>
                        <span class="font-bold text-primary text-xs sm:text-sm">{{ $seg->total }}</span>
                    </li>
                    @empty
                    <li class="text-label-sm text-outline text-xs">Belum ada segmen/grup.</li>
                    @endforelse
                </ul>
            </div>
            
            <div class="md:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg shadow-sm">
                <h4 class="font-headline-md text-headline-md text-sm sm:text-base mb-2 sm:mb-md">Tips Manajemen Kontak</h4>
                <ul class="space-y-1 sm:space-y-sm text-xs sm:text-body-md text-on-surface-variant">
                    <li class="flex items-start gap-1 sm:gap-sm">
                        <span class="material-symbols-outlined text-primary text-[16px] sm:text-[20px] mt-0.5">check_circle</span>
                        <span>Gunakan format nomor <code class="bg-surface-container px-0.5 sm:px-xs py-0.5 rounded text-primary font-bold">628xxx</code>.</span>
                    </li>
                    <li class="flex items-start gap-1 sm:gap-sm">
                        <span class="material-symbols-outlined text-primary text-[16px] sm:text-[20px] mt-0.5">check_circle</span>
                        <span>Import CSV: <strong>Nomor, Nama, Label</strong>.</span>
                    </li>
                    <li class="flex items-start gap-1 sm:gap-sm">
                        <span class="material-symbols-outlined text-primary text-[16px] sm:text-[20px] mt-0.5">check_circle</span>
                        <span>Gunakan <strong>Bulk Broadcast</strong> untuk pesan massal.</span>
                    </li>
                    <li class="flex items-start gap-1 sm:gap-sm">
                        <span class="material-symbols-outlined text-primary text-[16px] sm:text-[20px] mt-0.5">check_circle</span>
                        <span>Buat <strong>Label/Grup</strong> untuk mengelompokkan kontak.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal Import Berkas CSV -->
    <div id="importContactsModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Import Contacts (Excel/CSV)</h3>
                <button onclick="document.getElementById('importContactsModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('contacts.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Pilih Berkas CSV</label>
                        <input type="file" name="excel_file" accept=".csv,.txt" required class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-sm focus:ring-primary focus:border-primary text-body-md text-on-surface">
                    </div>
                    <div class="bg-primary/5 border border-primary/10 rounded-lg p-sm space-y-xs">
                        <p class="text-label-sm text-primary font-bold flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[18px]">info</span>
                            Panduan Format File CSV Kontak:
                        </p>
                        <ul class="list-disc pl-md space-y-xs text-[11px] text-on-surface-variant/80">
                            <li>Kolom A (Kolom 1): Nomor WhatsApp (misal: 081234567890).</li>
                            <li>Kolom B (Kolom 2): Nama Kontak (misal: Budi).</li>
                            <li>Kolom C (Kolom 3): Tag/Label Grup (misal: Reseller).</li>
                            <li>Gunakan pemisah koma (`,`) atau titik koma (`;`).</li>
                        </ul>
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('importContactsModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Cancel</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Import Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Bulk Add to Group -->
    <div id="bulkGroupModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Tambah Kontak ke Grup</h3>
                <button onclick="document.getElementById('bulkGroupModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-lg space-y-md">
                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-xs">Nama Grup/Label Baru</label>
                    <input type="text" id="bulkGroupName" placeholder="Cth: Reseller, Pelanggan VIP" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                </div>
            </div>
            <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                <button type="button" onclick="document.getElementById('bulkGroupModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Batal</button>
                <button onclick="submitBulkGroup()" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Broadcast -->
    <div id="bulkBroadcastModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Kirim Broadcast ke Kontak Terpilih</h3>
                <button onclick="document.getElementById('bulkBroadcastModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="bulkBroadcastForm" enctype="multipart/form-data">
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Pesan Broadcast</label>
                        <textarea id="bulkBroadcastMessage" name="message" rows="4" placeholder="Ketik pesan broadcast..." class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface"></textarea>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Lampiran (Opsional)</label>
                        <input type="file" name="attachment" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Gambar, video, audio, dokumen (max 16MB)</span>
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('bulkBroadcastModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Batal</button>
                    <button type="button" onclick="submitBulkBroadcast()" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Kirim Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Kontak -->
    <div id="addContactModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Add New Contact</h3>
                <button onclick="document.getElementById('addContactModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('contacts.store') }}" method="POST">
                @csrf
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Contact Name</label>
                        <input type="text" name="name" required placeholder="Cth: John Doe" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">WhatsApp Number</label>
                        <input type="text" name="phone_number" required placeholder="Cth: 628123456789" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Gunakan kode negara tanpa tanda "+" (cth: 628xxx)</span>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Tag / Group (Optional)</label>
                        <input type="text" name="label" placeholder="Cth: Reseller, Pelanggan, Leads" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('addContactModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Cancel</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Save Contact</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kontak -->
    <div id="editContactModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Edit Contact</h3>
                <button onclick="document.getElementById('editContactModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="editContactForm">
                @csrf
                @method('PUT')
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Contact Name</label>
                        <input type="text" id="editName" name="name" required placeholder="Cth: John Doe" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">WhatsApp Number</label>
                        <input type="text" id="editPhoneNumber" name="phone_number" required placeholder="Cth: 628123456789" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Tag / Group (Optional)</label>
                        <input type="text" id="editLabel" name="label" placeholder="Cth: Reseller, Pelanggan, Leads" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                    </div>
                    <div class="flex items-center justify-between pt-sm border-t border-outline-variant/30">
                        <div>
                            <p class="font-bold text-on-surface text-sm">Active Contact</p>
                            <p class="text-label-sm text-on-surface-variant">Nonaktifkan untuk mengecualikan dari campaign.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="editIsActive" name="is_active" value="1" class="sr-only peer">
                            <div class="w-11 h-6 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('editContactModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Cancel</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Update Contact</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Direct Message -->
    <div id="directMessageModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Kirim Pesan Langsung</h3>
                <button onclick="document.getElementById('directMessageModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="directMessageForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Penerima</label>
                        <input type="text" id="dmRecipient" readonly class="w-full bg-surface-container-low border border-outline-variant px-md py-sm rounded-lg text-on-surface-variant outline-none">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Pesan WhatsApp</label>
                        <textarea name="message" required rows="4" placeholder="Ketik pesan Anda di sini..." class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface"></textarea>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Lampiran (Opsional)</label>
                        <input type="file" name="attachment" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Gambar, video, audio, dokumen (max 16MB)</span>
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('directMessageModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Batal</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Kirim Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Schedule -->
    <div id="scheduleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Jadwalkan Pesan</h3>
                <button onclick="document.getElementById('scheduleModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="scheduleForm" method="POST" action="{{ route('campaigns.store') }}">
                @csrf
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Penerima</label>
                        <input type="text" id="scheduleRecipient" readonly class="w-full bg-surface-container-low border border-outline-variant px-md py-sm rounded-lg text-on-surface-variant outline-none">
                        <input type="hidden" name="target_type" value="manual">
                        <input type="hidden" name="manual_numbers" id="scheduleNumber">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Nama Jadwal</label>
                        <input type="text" name="name" required class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary" placeholder="cth: Follow Up">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Pesan</label>
                        <textarea name="message_template" required rows="4" placeholder="Ketik pesan..." class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Jadwal Kirim</label>
                        <input type="datetime-local" name="scheduled_at" required class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    <input type="hidden" name="is_scheduled" value="on">
                    <input type="hidden" name="schedule_type" value="once">
                    <input type="hidden" name="is_active" value="on">
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Batal</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Jadwalkan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openEditModal(contactId, name, phoneNumber, label, isActive) {
            const form = document.getElementById('editContactForm');
            form.action = `/contacts/${contactId}`;
            document.getElementById('editName').value = name;
            document.getElementById('editPhoneNumber').value = phoneNumber;
            document.getElementById('editLabel').value = label;
            document.getElementById('editIsActive').checked = !!isActive;
            document.getElementById('editContactModal').classList.remove('hidden');
        }

        document.getElementById('editContactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-HTTP-Method-Override': 'PUT',
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editContactModal').classList.add('hidden');
                    location.reload();
                } else {
                    alert('Gagal memperbarui kontak.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan.');
            });
        });

        function openDirectMessageModal(contactId, name, phoneNumber) {
            const form = document.getElementById('directMessageForm');
            form.action = `/contacts/${contactId}/send-message`;
            
            const recipientInput = document.getElementById('dmRecipient');
            recipientInput.value = `${name} (${phoneNumber})`;
            
            document.getElementById('directMessageModal').classList.remove('hidden');
        }

        const selectAll = document.getElementById('selectAll');
        let checkboxes = document.querySelectorAll('.contact-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCountSpan = document.getElementById('selectedCount');

        function updateBulkActions() {
            const checkedCount = document.querySelectorAll('.contact-checkbox:checked').length;
            if (checkedCount > 0) {
                bulkActions.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
                bulkActions.classList.add('opacity-100', 'translate-y-0');
                selectedCountSpan.textContent = checkedCount;
            } else {
                bulkActions.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
                bulkActions.classList.remove('opacity-100', 'translate-y-0');
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                checkboxes = document.querySelectorAll('.contact-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                });
                updateBulkActions();
            });
        }

        function initCheckboxes() {
            checkboxes = document.querySelectorAll('.contact-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    updateBulkActions();
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    const someChecked = Array.from(checkboxes).some(c => c.checked);
                    if (selectAll) {
                        selectAll.checked = allChecked;
                        selectAll.indeterminate = someChecked && !allChecked;
                    }
                });
            });
        }

        initCheckboxes();

        function deselectAll() {
            checkboxes = document.querySelectorAll('.contact-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            updateBulkActions();
        }

        function deleteContact(event, contactId) {
            event.preventDefault();
            if (!confirm('Apakah Anda yakin ingin menghapus kontak ini?')) return;

            const row = document.getElementById(`contact-row-${contactId}`);
            
            fetch(`/contacts/${contactId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    row.style.transition = 'all 0.4s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        row.remove();
                        checkboxes = document.querySelectorAll('.contact-checkbox');
                        if (checkboxes.length === 0) {
                            location.reload();
                        } else {
                            updateBulkActions();
                        }
                    }, 400);
                } else {
                    alert('Gagal menghapus kontak.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan.');
            });
        }

        function bulkDeleteContacts() {
            const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
            if (checkedBoxes.length === 0) return;
            if (!confirm(`Apakah Anda yakin ingin menghapus ${checkedBoxes.length} kontak terpilih?`)) return;

            const ids = Array.from(checkedBoxes).map(cb => cb.value);

            fetch('/contacts/bulk-delete', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => {
                if (response.ok) {
                    checkedBoxes.forEach(cb => {
                        const row = document.getElementById(`contact-row-${cb.value}`);
                        if (row) {
                            row.style.transition = 'all 0.4s ease-out';
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-20px)';
                        }
                    });
                    setTimeout(() => {
                        checkedBoxes.forEach(cb => {
                            const row = document.getElementById(`contact-row-${cb.value}`);
                            if (row) row.remove();
                        });
                        deselectAll();
                        checkboxes = document.querySelectorAll('.contact-checkbox');
                        if (checkboxes.length === 0) {
                            location.reload();
                        }
                    }, 400);
                } else {
                    alert('Gagal menghapus kontak terpilih.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan.');
            });
        }

        function submitBulkGroup() {
            const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
            if (checkedBoxes.length === 0) return;
            const label = document.getElementById('bulkGroupName').value.trim();
            if (!label) {
                alert('Silakan masukkan nama grup/label.');
                return;
            }

            const ids = Array.from(checkedBoxes).map(cb => cb.value);

            fetch('/contacts/bulk-add-group', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids, label: label })
            })
            .then(response => {
                if (response.ok) {
                    alert('Grup/label kontak terpilih berhasil diperbarui.');
                    location.reload();
                } else {
                    alert('Gagal memperbarui grup.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan.');
            });
        }

        function submitBulkBroadcast() {
            const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
            if (checkedBoxes.length === 0) return;
            const message = document.getElementById('bulkBroadcastMessage').value.trim();
            if (!message) {
                alert('Silakan masukkan pesan broadcast.');
                return;
            }

            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const fileInput = document.querySelector('#bulkBroadcastForm input[name="attachment"]');

            const formData = new FormData();
            formData.append('message', message);
            formData.append('_token', '{{ csrf_token() }}');
            ids.forEach(id => formData.append('ids[]', id));
            if (fileInput.files.length > 0) {
                formData.append('attachment', fileInput.files[0]);
            }

            const btn = document.querySelector('#bulkBroadcastModal button[onclick="submitBulkBroadcast()"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Mengirim...';

            fetch('/contacts/bulk-broadcast', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = originalText;
                if (data.success) {
                    alert(data.message);
                    document.getElementById('bulkBroadcastModal').classList.add('hidden');
                    document.getElementById('bulkBroadcastMessage').value = '';
                    fileInput.value = '';
                    deselectAll();
                } else {
                    alert('Gagal mengirim broadcast.');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = originalText;
                console.error('Error:', error);
                alert('Terjadi kesalahan.');
            });
        }

        // Toggle active status
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('contact-toggle-active')) {
                const contactId = e.target.dataset.id;
                const checkbox = e.target;
                const slider = checkbox.nextElementSibling;

                fetch(`/contacts/${contactId}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_active) {
                            slider.className = 'w-10 h-5 bg-green-500 rounded-full transition-all before:content-[""] before:absolute before:top-[2px] before:left-[2px] before:bg-white before:rounded-full before:h-4 before:w-4 before:transition-all before:translate-x-5';
                        } else {
                            slider.className = 'w-10 h-5 bg-outline-variant rounded-full transition-all before:content-[""] before:absolute before:top-[2px] before:left-[2px] before:bg-white before:rounded-full before:h-4 before:w-4 before:transition-all';
                        }
                    } else {
                        checkbox.checked = !checkbox.checked;
                        alert('Gagal mengubah status kontak.');
                    }
                })
                .catch(() => {
                    checkbox.checked = !checkbox.checked;
                    alert('Terjadi kesalahan.');
                });
            }
        });

        function mergeDuplicates(phoneNumber) {
            if (!confirm(`Gabungkan semua duplikat untuk nomor ${phoneNumber}?`)) return;
            fetch('/contacts/merge-duplicates', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone_number: phoneNumber })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { location.reload(); }
                else { alert(data.message); }
            });
        }

        function openScheduleModal(contactId, name, phoneNumber) {
            document.getElementById('scheduleRecipient').value = `${name} (${phoneNumber})`;
            document.getElementById('scheduleNumber').value = phoneNumber;
            document.getElementById('scheduleModal').classList.remove('hidden');
        }
    </script>
@endpush
