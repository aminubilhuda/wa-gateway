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
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-md">
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface">Auto Reply Management</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant mt-xs">Set up automated responses based on keywords.</p>
            </div>
            <button onclick="document.getElementById('addRuleModal').classList.remove('hidden')" class="bg-primary-container text-on-primary-container px-lg py-sm rounded-lg font-bold flex items-center gap-xs hover:shadow-lg active:scale-95 transition-all">
                <span class="material-symbols-outlined" data-icon="add">add</span>
                Tambah Aturan Baru
            </button>
        </div>
        
        <!-- Stats Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
            <div class="bg-surface-container-lowest border border-outline-variant p-lg rounded-xl flex items-center gap-lg">
                <div class="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[32px]" data-icon="auto_awesome">auto_awesome</span>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant uppercase tracking-wider">Total Auto-Replies</p>
                    <h3 class="text-display font-display text-on-surface">{{ number_format($totalTriggers) }}</h3>
                </div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant p-lg rounded-xl flex items-center gap-lg">
                <div class="h-14 w-14 rounded-full bg-secondary-container/40 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[32px]" data-icon="key">key</span>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant uppercase tracking-wider">Top Keyword</p>
                    <h3 class="text-display font-display text-on-surface">{{ $topKeyword }}</h3>
                </div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant p-lg rounded-xl relative overflow-hidden">
                <div class="absolute -right-4 -top-4 opacity-5">
                    <span class="material-symbols-outlined text-[120px]" data-icon="trending_up">trending_up</span>
                </div>
                <p class="text-label-md text-on-surface-variant uppercase tracking-wider">Success Rate</p>
                <h3 class="text-display font-display text-on-surface">{{ $successRate }}%</h3>
                <p class="text-label-sm text-primary flex items-center gap-xs mt-xs">
                    <span class="material-symbols-outlined text-[14px]" data-icon="check_circle">check_circle</span>
                    Based on auto reply logs
                </p>
            </div>
        </div>
        
        <!-- Rules Table Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden auto-reply-card">
            <div class="px-lg py-md bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
                <h4 class="font-headline-md text-headline-md text-on-surface">Existing Rules</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <tbody class="divide-y divide-outline-variant">
                        <tr class="bg-surface-container-low text-label-md text-on-surface-variant uppercase tracking-wider">
                            <td class="px-lg py-md font-medium">Keyword</td>
                            <td class="px-lg py-md font-medium">Response Message</td>
                            <td class="px-lg py-md font-medium">Type</td>
                            <td class="px-lg py-md font-medium">Attachment</td>
                            <td class="px-lg py-md font-medium">Match Type</td>
                            <td class="px-lg py-md font-medium">Triggers</td>
                            <td class="px-lg py-md font-medium">Status</td>
                            <td class="px-lg py-md font-medium text-right">Actions</td>
                        </tr>
                        @forelse($rules as $rule)
                        @php
                            $depth = 0;
                            $current = $rule;
                            while ($current->parent_id) {
                                $depth++;
                                $current = $current->parent;
                            }
                            $rowClass = $depth === 0 ? '' : ($depth === 1 ? 'child-row' : 'grandchild-row');
                        @endphp
                        <tr class="hover:bg-surface-container transition-colors group {{ !$rule->is_active ? 'opacity-60' : '' }} {{ $rowClass }}">
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-xs">
                                    @if($depth === 1)
                                        <span class="material-symbols-outlined text-[16px] text-outline">subdirectory_arrow_right</span>
                                    @elseif($depth >= 2)
                                        <span class="material-symbols-outlined text-[16px] text-outline">double_arrow</span>
                                    @elseif($rule->children->count() > 0)
                                        <span class="material-symbols-outlined text-[16px] text-primary">account_tree</span>
                                    @endif
                                    <span class="bg-primary/10 text-primary px-sm py-1 rounded-full font-bold text-label-md">{{ $rule->keyword }}</span>
                                </div>
                            </td>
                            <td class="px-lg py-md">
                                <p class="text-body-md text-on-surface max-w-xs truncate">{{ $rule->response_message }}</p>
                            </td>
                            <td class="px-lg py-md">
                                @if($rule->children->count() > 0 && $depth === 0)
                                    <span class="text-label-sm text-primary flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]">account_tree</span>
                                        Parent ({{ $rule->children->count() }} children)
                                    </span>
                                @elseif($depth > 0)
                                    <span class="text-label-sm text-secondary flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]">subdirectory_arrow_right</span>
                                        Child of: {{ $rule->parent->keyword ?? '-' }}
                                    </span>
                                @else
                                    <span class="text-label-sm text-outline">Standalone</span>
                                @endif
                            </td>
                            <td class="px-lg py-md">
                                @if($rule->attachment_path)
                                    <span class="text-label-sm text-primary flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                        {{ ucfirst($rule->attachment_type ?? 'file') }}
                                    </span>
                                @else
                                    <span class="text-label-sm text-outline">—</span>
                                @endif
                            </td>
                            <td class="px-lg py-md">
                                <span class="text-label-md text-on-surface-variant border border-outline-variant px-xs py-0.5 rounded">{{ ucfirst($rule->match_type) }}</span>
                            </td>
                            <td class="px-lg py-md font-bold text-body-md text-on-surface">
                                {{ number_format($rule->trigger_count) }}x
                            </td>
                            <td class="px-lg py-md">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input class="sr-only toggle-switch" type="checkbox" data-id="{{ $rule->id }}" {{ $rule->is_active ? 'checked' : '' }}/>
                                    <div class="w-10 h-5 bg-outline-variant rounded-full transition-all toggle-slider relative before:content-[''] before:absolute before:top-[2px] before:left-[2px] before:bg-white before:rounded-full before:h-4 before:w-4 before:transition-all"></div>
                                </label>
                            </td>
                            <td class="px-lg py-md text-right">
                                <div class="flex justify-end gap-base">
                                    <button onclick="openEditModal({{ $rule->id }}, {{ \Illuminate\Support\Js::from($rule->keyword) }}, {{ \Illuminate\Support\Js::from($rule->match_type) }}, {{ \Illuminate\Support\Js::from($rule->response_message) }}, {{ \Illuminate\Support\Js::from($rule->attachment_type ?? '') }}, {{ \Illuminate\Support\Js::from($rule->attachment_path ?? '') }}, {{ $rule->parent_id ?? 'null' }})" class="p-xs text-on-secondary-fixed-variant hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined" data-icon="edit">edit</span>
                                    </button>
                                    <form action="{{ route('auto-reply.destroy', $rule->id) }}" method="POST" onsubmit="return confirm('Hapus aturan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-xs text-on-secondary-fixed-variant hover:text-error transition-colors">
                                            <span class="material-symbols-outlined" data-icon="delete">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-lg py-md text-center text-on-surface-variant">Belum ada aturan balas otomatis.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-lg py-md bg-surface-container-low border-t border-outline-variant flex justify-between items-center">
                <p class="text-label-md text-on-surface-variant">Showing {{ $rules->count() }} rules</p>
            </div>
        </div>
        
        <!-- Helpful Tips Card -->
        <div class="bg-primary/5 border border-primary/20 p-lg rounded-xl flex gap-lg items-start">
            <div class="bg-primary text-white p-sm rounded-lg">
                <span class="material-symbols-outlined" data-icon="tips_and_updates">tips_and_updates</span>
            </div>
            <div class="space-y-xs">
                <h5 class="font-bold text-on-primary-container font-body-lg text-body-lg">Pro Tip: Buat Menu Bertingkat (Tree)</h5>
                <p class="text-body-md text-on-primary-container/80">Buat menu utama (misal: <strong>"info"</strong>), lalu buat sub-menu dengan memilih menu induk. User yang mengetik "info" akan melihat daftar sub-menu. Session akan aktif selama 10 menit. Ketik <strong>"back"</strong> atau <strong>"menu"</strong> untuk kembali ke menu utama.</p>
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
