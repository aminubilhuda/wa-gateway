@extends('layouts.app')

@push('styles')
    <style>
        .toggle-switch:checked + .toggle-slider {
            background-color: #25d366;
        }
        .toggle-switch:checked + .toggle-slider:before {
            transform: translateX(1.25rem);
        }
        .child-row td:first-child {
            padding-left: 3rem !important;
        }
        .child-row {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .grandchild-row td:first-child {
            padding-left: 4.5rem !important;
        }
        .grandchild-row {
            background-color: rgba(0, 0, 0, 0.04);
        }
    </style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto space-y-lg">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-md">
        <div>
            <h1 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface">Auto Reply Management</h1>
            <p class="text-body-lg text-body-lg text-on-surface-variant mt-xs text-sm">Set up automated responses based on keywords.</p>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="#conversations-section" class="px-2 sm:px-md py-1 sm:py-1.5 border border-outline-variant text-on-surface rounded-lg font-bold hover:bg-surface-container-low transition-colors text-xs sm:text-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">chat</span>
                <span class="hidden sm:inline">Conversations</span>
            </a>
            <button onclick="document.getElementById('addRuleModal').classList.remove('hidden')" class="bg-primary-container text-on-primary-container px-3 sm:px-lg py-1.5 sm:py-sm rounded-lg font-bold flex items-center gap-1 sm:gap-xs hover:shadow-lg active:scale-95 transition-all text-xs sm:text-sm w-full sm:w-auto justify-center">
                <span class="material-symbols-outlined text-[16px] sm:text-[20px]" data-icon="add">add</span>
                Tambah Aturan
            </button>
        </div>
    </div>
    
    <!-- Stats Bento Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-lg">
        <div class="bg-surface-container-lowest border border-outline-variant p-3 sm:p-lg rounded-xl flex items-center gap-3 sm:gap-lg">
            <div class="h-8 w-8 sm:h-14 sm:w-14 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[18px] sm:text-[32px]" data-icon="auto_awesome">auto_awesome</span>
            </div>
            <div>
                <p class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Total Auto-Replies</p>
                <h3 class="text-lg sm:text-display font-display text-on-surface">{{ number_format($totalTriggers) }}</h3>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-3 sm:p-lg rounded-xl flex items-center gap-3 sm:gap-lg">
            <div class="h-8 w-8 sm:h-14 sm:w-14 rounded-full bg-secondary-container/40 flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[18px] sm:text-[32px]" data-icon="key">key</span>
            </div>
            <div>
                <p class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Top Keyword</p>
                <h3 class="text-lg sm:text-display font-display text-on-surface truncate">{{ $topKeyword }}</h3>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-3 sm:p-lg rounded-xl relative overflow-hidden">
            <div class="absolute -right-4 -top-4 opacity-5">
                <span class="material-symbols-outlined text-[60px] sm:text-[120px]" data-icon="trending_up">trending_up</span>
            </div>
            <p class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Success Rate</p>
            <h3 class="text-lg sm:text-display font-display text-on-surface">{{ $successRate }}%</h3>
            <p class="text-[10px] sm:text-label-sm text-primary flex items-center gap-1 sm:gap-xs mt-0.5 sm:mt-xs">
                <span class="material-symbols-outlined text-[12px] sm:text-[14px]" data-icon="check_circle">check_circle</span>
                Based on auto reply logs
            </p>
        </div>
    </div>
    
    <!-- Rules Table Card -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden auto-reply-card">
        <div class="px-3 sm:px-lg py-2 sm:py-md bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
            <h4 class="font-headline-md text-headline-md text-sm sm:text-base text-on-surface">Existing Rules</h4>
        </div>
        <div class="overflow-x-auto overflow-y-hidden">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <tbody class="divide-y divide-outline-variant">
                    <tr class="bg-surface-container-low text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Keyword</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Response</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Type</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Attach</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Match</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">#</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Status</td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium text-right">Actions</td>
                    </tr>
                    @forelse($rules as $rule)
                    @php
                        $depth = 0;
                        $current = $rule;
                        while ($current->parent_id) { $depth++; $current = $current->parent; }
                        $rowClass = $depth === 0 ? '' : ($depth === 1 ? 'child-row' : 'grandchild-row');
                    @endphp
                    <tr class="hover:bg-surface-container transition-colors group {{ !$rule->is_active ? 'opacity-60' : '' }} {{ $rowClass }}">
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            <div class="flex items-center gap-0.5 sm:gap-xs">
                                @if($depth === 1)
                                    <span class="material-symbols-outlined text-[12px] sm:text-[16px] text-outline">subdirectory_arrow_right</span>
                                @elseif($depth >= 2)
                                    <span class="material-symbols-outlined text-[12px] sm:text-[16px] text-outline">double_arrow</span>
                                @elseif($rule->children->count() > 0)
                                    <span class="material-symbols-outlined text-[12px] sm:text-[16px] text-primary">account_tree</span>
                                @endif
                                <span class="bg-primary/10 text-primary px-1 sm:px-sm py-0.5 sm:py-1 rounded-full font-bold text-[10px] sm:text-label-md">{{ $rule->keyword }}</span>
                            </div>
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            <p class="text-xs sm:text-body-md text-on-surface max-w-[80px] sm:max-w-xs truncate">{{ $rule->response_message }}</p>
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            @if($rule->children->count() > 0 && $depth === 0)
                                <span class="text-[9px] sm:text-label-sm text-primary flex items-center gap-0.5 sm:gap-xs">
                                    <span class="material-symbols-outlined text-[12px] sm:text-[16px]">account_tree</span>
                                    <span class="hidden sm:inline">Parent</span>
                                </span>
                            @elseif($depth > 0)
                                <span class="text-[9px] sm:text-label-sm text-secondary flex items-center gap-0.5 sm:gap-xs">
                                    <span class="material-symbols-outlined text-[12px] sm:text-[16px]">subdirectory_arrow_right</span>
                                    <span class="hidden sm:inline">Child</span>
                                </span>
                            @else
                                <span class="text-[9px] sm:text-label-sm text-outline">Standalone</span>
                            @endif
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            @if($rule->attachment_path)
                                <span class="text-[9px] sm:text-label-sm text-primary flex items-center gap-0.5 sm:gap-xs">
                                    <span class="material-symbols-outlined text-[12px] sm:text-[16px]">attach_file</span>
                                    <span class="hidden sm:inline">{{ ucfirst($rule->attachment_type ?? 'file') }}</span>
                                </span>
                            @else
                                <span class="text-[9px] sm:text-label-sm text-outline">—</span>
                            @endif
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            <span class="text-[10px] sm:text-label-md text-on-surface-variant border border-outline-variant px-0.5 sm:px-xs py-0.5 rounded">{{ ucfirst($rule->match_type) }}</span>
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md font-bold text-xs sm:text-body-md text-on-surface">
                            {{ number_format($rule->trigger_count) }}x
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input class="sr-only toggle-switch" type="checkbox" data-id="{{ $rule->id }}" {{ $rule->is_active ? 'checked' : '' }}/>
                                <div class="w-7 sm:w-10 h-3.5 sm:h-5 bg-outline-variant rounded-full transition-all toggle-slider relative before:content-[''] before:absolute before:top-[1px] sm:before:top-[2px] before:left-[1px] sm:before:left-[2px] before:bg-white before:rounded-full before:h-2.5 sm:before:h-4 before:w-2.5 sm:before:w-4 before:transition-all"></div>
                            </label>
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md text-right">
                            <div class="flex justify-end gap-1 sm:gap-base">
                                <button onclick="openEditModal({{ $rule->id }}, {{ \Illuminate\Support\Js::from($rule->keyword) }}, {{ \Illuminate\Support\Js::from($rule->match_type) }}, {{ \Illuminate\Support\Js::from($rule->response_message) }}, {{ \Illuminate\Support\Js::from($rule->attachment_type ?? '') }}, {{ \Illuminate\Support\Js::from($rule->attachment_path ?? '') }}, {{ $rule->parent_id ?? 'null' }})" class="p-0.5 sm:p-xs text-on-secondary-fixed-variant hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[16px] sm:text-[20px]" data-icon="edit">edit</span>
                                </button>
                                <form action="{{ route('auto-reply.destroy', $rule->id) }}" method="POST" onsubmit="return confirm('Hapus aturan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-0.5 sm:p-xs text-on-secondary-fixed-variant hover:text-error transition-colors">
                                        <span class="material-symbols-outlined text-[16px] sm:text-[20px]" data-icon="delete">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-2 sm:px-lg py-1.5 sm:py-md text-center text-on-surface-variant text-xs">Belum ada aturan balas otomatis.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 sm:px-lg py-2 sm:py-md bg-surface-container-low border-t border-outline-variant flex justify-between items-center">
            <p class="text-[10px] sm:text-label-md text-on-surface-variant">Showing {{ $rules->count() }} rules</p>
        </div>
    </div>
    
    <!-- Helpful Tips Card -->
    <div class="bg-primary/5 border border-primary/20 p-3 sm:p-lg rounded-xl flex gap-3 sm:gap-lg items-start">
        <div class="bg-primary text-white p-1.5 sm:p-sm rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-[18px] sm:text-[24px]" data-icon="tips_and_updates">tips_and_updates</span>
        </div>
        <div class="space-y-1 sm:space-y-xs min-w-0">
            <h5 class="font-bold text-on-primary-container font-body-lg text-body-lg text-sm">Pro Tip: Buat Menu Bertingkat (Tree)</h5>
            <p class="text-xs sm:text-body-md text-on-primary-container/80">Buat menu utama, lalu sub-menu. User yang mengetik keyword akan melihat sub-menu. Ketik <strong>"back"</strong> untuk kembali.</p>
        </div>
    </div>
    </div>

    <!-- Conversations Section -->
    <div id="conversations-section" class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden mt-3 sm:mt-lg">
        <div class="px-3 sm:px-lg py-2 sm:py-md bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
            <h4 class="font-headline-md text-headline-md text-sm sm:text-base text-on-surface">Recent Incoming Conversations</h4>
            <span class="text-[10px] sm:text-label-md text-outline">Auto-reply & direct messages</span>
        </div>
        <div class="overflow-x-auto overflow-y-hidden">
            <table class="w-full text-left border-collapse min-w-[500px]">
                <thead>
                    <tr class="bg-surface-container-low/50 text-[10px] sm:text-label-sm text-outline border-b border-outline-variant">
                        <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Contact</th>
                        <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Last Message</th>
                        <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Status</th>
                        <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Time</th>
                        <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @php
                        $conversations = \App\Models\MessageLog::whereNull('campaign_id')
                            ->with('contact')
                            ->select('contact_id', \DB::raw('MAX(created_at) as last_time, COUNT(*) as msg_count'))
                            ->groupBy('contact_id')
                            ->orderBy('last_time', 'desc')
                            ->limit(20)
                            ->get();
                    @endphp
                    @forelse($conversations as $conv)
                    <tr class="hover:bg-surface-container/50 transition-colors">
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            <span class="font-bold text-xs sm:text-body-md text-on-surface">{{ $conv->contact->name ?? 'Unknown' }}</span>
                            <p class="text-[10px] sm:text-label-sm text-outline">{{ $conv->contact->phone_number ?? '' }}</p>
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md text-xs text-on-surface-variant truncate max-w-[120px] sm:max-w-xs">
                            {{ \App\Models\MessageLog::where('contact_id', $conv->contact_id)->whereNull('campaign_id')->latest()->value('message_body') }}
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                            <span class="text-xs text-on-surface-variant">{{ $conv->msg_count }} messages</span>
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md text-[10px] sm:text-label-sm text-outline whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($conv->last_time)->diffForHumans() }}
                        </td>
                        <td class="px-2 sm:px-lg py-1.5 sm:py-md text-right">
                            <button onclick="showConversation({{ $conv->contact_id }})" class="text-primary hover:text-primary/80 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">forum</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-2 sm:px-lg py-1.5 sm:py-md text-center text-on-surface-variant text-xs">Belum ada percakapan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="conversationModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-headline-sm text-headline-sm text-on-surface" id="conversationTitle">Conversation</h3>
                <button onclick="document.getElementById('conversationModal').classList.add('hidden')" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="conversationMessages" class="flex-1 overflow-y-auto p-lg space-y-3 bg-surface-container-low">
                <div class="text-center text-on-surface-variant text-xs">Loading...</div>
            </div>
            <div class="px-lg py-md border-t border-outline-variant bg-white">
                <button onclick="document.getElementById('conversationModal').classList.add('hidden')" class="w-full py-sm bg-primary text-white rounded-lg font-bold hover:brightness-105">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Aturan -->
    <div id="addRuleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Tambah Aturan Baru</h3>
                <button onclick="document.getElementById('addRuleModal').classList.add('hidden')" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('auto-reply.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Keyword Pemicu</label>
                        <input type="text" name="keyword" required placeholder="Cth: Harga, Halo, Info" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Menu Induk (Opsional)</label>
                        <select name="parent_id" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                            <option value="">— Tidak ada (Menu Utama) —</option>
                            @foreach($parentMenus as $parent)
                                @php
                                    $label = $parent->keyword;
                                    $current = $parent;
                                    $depth = 0;
                                    while ($current->parent_id) {
                                        $depth++;
                                        $current = $current->parent;
                                    }
                                    if ($depth > 0) {
                                        $label = str_repeat('  ', $depth) . '└ ' . $label;
                                    }
                                @endphp
                                <option value="{{ $parent->id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Kosongkan jika ini menu utama. Pilih menu induk jika ini sub-menu.</span>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Tipe Pencocokan (Match Type)</label>
                        <select name="match_type" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                            <option value="exact">Sama Persis (Exact Match)</option>
                            <option value="contains">Mengandung Kata (Contains)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Pesan Balasan</label>
                        <textarea name="response_message" required rows="4" placeholder="Ketik balasan otomatis Anda di sini..." class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Lampiran (Opsional)</label>
                        <input type="file" name="attachment" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Gambar, video, audio, dokumen (max 16MB)</span>
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('addRuleModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Batal</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Simpan Aturan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Aturan -->
    <div id="editRuleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Edit Aturan</h3>
                <button onclick="document.getElementById('editRuleModal').classList.add('hidden')" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="editRuleForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="p-lg space-y-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Keyword Pemicu</label>
                        <input type="text" id="editKeyword" name="keyword" required placeholder="Cth: Harga, Halo, Info" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Menu Induk (Opsional)</label>
                        <select id="editParentId" name="parent_id" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                            <option value="">— Tidak ada (Menu Utama) —</option>
                            @foreach($parentMenus as $parent)
                                @php
                                    $label = $parent->keyword;
                                    $current = $parent;
                                    $depth = 0;
                                    while ($current->parent_id) {
                                        $depth++;
                                        $current = $current->parent;
                                    }
                                    if ($depth > 0) {
                                        $label = str_repeat('  ', $depth) . '└ ' . $label;
                                    }
                                @endphp
                                <option value="{{ $parent->id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Kosongkan jika ini menu utama. Pilih menu induk jika ini sub-menu.</span>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Tipe Pencocokan (Match Type)</label>
                        <select id="editMatchType" name="match_type" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary">
                            <option value="exact">Sama Persis (Exact Match)</option>
                            <option value="contains">Mengandung Kata (Contains)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Pesan Balasan</label>
                        <textarea id="editResponseMessage" name="response_message" required rows="4" placeholder="Ketik balasan otomatis Anda di sini..." class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-xs">Lampiran (Opsional)</label>
                        <input type="file" name="attachment" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-lg focus:outline-none focus:border-primary text-on-surface">
                        <span class="text-xs text-on-surface-variant/70 mt-1 block">Gambar, video, audio, dokumen (max 16MB)</span>
                        <div id="currentAttachment" class="mt-2 hidden">
                            <p class="text-xs text-on-surface-variant mb-1">Lampiran saat ini:</p>
                            <div id="attachmentPreview" class="max-w-xs"></div>
                        </div>
                    </div>
                </div>
                <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end gap-sm">
                    <button type="button" onclick="document.getElementById('editRuleModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold text-on-surface-variant hover:bg-surface-variant rounded-lg">Batal</button>
                    <button type="submit" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Perbarui Aturan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openEditModal(id, keyword, matchType, responseMessage, attachmentType, attachmentPath, parentId) {
            document.getElementById('editRuleForm').action = `/auto-reply/${id}`;
            document.getElementById('editKeyword').value = keyword;
            document.getElementById('editMatchType').value = matchType;
            document.getElementById('editResponseMessage').value = responseMessage;
            document.getElementById('editParentId').value = parentId || '';
            const containerEl = document.getElementById('currentAttachment');
            const previewEl = document.getElementById('attachmentPreview');
            if (attachmentPath) {
                containerEl.classList.remove('hidden');
                let html = '';
                if (attachmentType === 'image') {
                    html = `<img src="${attachmentPath}" alt="Current Attachment" class="max-h-32 rounded-lg border border-outline-variant">`;
                } else if (attachmentType === 'video') {
                    html = `<video src="${attachmentPath}" controls class="max-h-32 rounded-lg border border-outline-variant"></video>`;
                } else if (attachmentType === 'audio') {
                    html = `<audio src="${attachmentPath}" controls class="w-full"></audio>`;
                } else {
                    html = `<a href="${attachmentPath}" target="_blank" class="text-primary text-xs underline">Lihat file</a>`;
                }
                previewEl.innerHTML = html;
            } else {
                containerEl.classList.add('hidden');
                previewEl.innerHTML = '';
            }
            document.getElementById('editRuleModal').classList.remove('hidden');
        }

        // Micro-interactions for toggles and atmospheric effects
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            // Set initial state
            const row = toggle.closest('tr');
            if (!toggle.checked) {
                row.classList.add('opacity-60');
            }

            toggle.addEventListener('change', function() {
                const ruleId = this.getAttribute('data-id');
                const isChecked = this.checked;

                if (isChecked) {
                    row.classList.remove('opacity-60');
                } else {
                    row.classList.add('opacity-60');
                }

                // Call backend via fetch
                fetch(`/auto-reply/${ruleId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).catch(err => console.error('Gagal toggle aturan', err));
            });
        });

        function showConversation(contactId) {
            const modal = document.getElementById('conversationModal');
            const container = document.getElementById('conversationMessages');
            document.getElementById('conversationTitle').textContent = 'Conversation';
            container.innerHTML = '<div class="text-center text-on-surface-variant text-xs py-8">Loading...</div>';
            modal.classList.remove('hidden');
            fetch(`/api/conversation/${contactId}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                container.innerHTML = '';
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-center text-on-surface-variant text-xs py-8">No messages</div>';
                    return;
                }
                document.getElementById('conversationTitle').textContent = 'Conversation with ' + (data[0].contact?.name || 'Unknown');
                data.forEach(msg => {
                    const isOutgoing = msg.status === 'sent' || msg.status === 'failed';
                    const div = document.createElement('div');
                    div.className = isOutgoing ? 'flex justify-end' : 'flex justify-start';
                    div.innerHTML = `
                        <div class="max-w-[75%] ${isOutgoing ? 'bg-primary-container text-on-primary-container' : 'bg-white text-on-surface'} rounded-lg p-3 shadow-sm">
                            <p class="text-xs sm:text-sm">${msg.message_body || '(empty)'}</p>
                            <div class="flex justify-end items-center gap-1 mt-1">
                                <span class="text-[9px] ${isOutgoing ? 'text-on-primary-container/70' : 'text-on-surface-variant'}">${msg.created_at ? new Date(msg.created_at).toLocaleString() : ''}</span>
                                ${msg.status === 'sent' ? '<span class="material-symbols-outlined text-[12px] text-blue-500">done_all</span>' : ''}
                                ${msg.status === 'failed' ? '<span class="material-symbols-outlined text-[12px] text-red-500">error</span>' : ''}
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                });
            })
            .catch(() => {
                container.innerHTML = '<div class="text-center text-red-500 text-xs py-8">Failed to load</div>';
            });
        }

        // Hover effect for stats cards
        document.querySelectorAll('.auto-reply-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
                card.style.boxShadow = '0 12px 24px -10px rgba(30, 41, 59, 0.08)';
                card.style.transition = 'all 0.3s ease';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = 'none';
            });
        });
    </script>
@endpush
