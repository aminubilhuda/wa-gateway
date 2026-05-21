@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto space-y-lg">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-xs text-on-surface-variant font-label-md text-label-md mb-lg">
            <a class="hover:text-primary" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-primary font-bold">Message Templates</span>
        </nav>

        <!-- Page Header -->
        <div class="flex justify-between items-start mb-xl">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-xs">Message Templates</h2>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl">
                    Kelola draf pesan WhatsApp Anda di sini untuk mempermudah pembuatan kampanye penyiaran (*Campaigns*).
                </p>
            </div>
            <button onclick="toggleAddTemplateModal(true)" class="bg-primary text-on-primary px-lg py-sm rounded-lg font-bold hover:brightness-110 active:scale-95 transition-all flex items-center gap-xs shadow-md">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span>Buat Templat</span>
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-lg py-md rounded-xl mb-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Templates Grid (Bento Style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
            @forelse($templates as $tmp)
                <div class="bg-surface-container-lowest border border-outline-variant p-lg rounded-xl flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="space-y-sm">
                        <div class="flex justify-between items-start">
                            <h4 class="font-bold text-headline-sm text-on-surface truncate pr-md">{{ $tmp->title }}</h4>
                            <span class="material-symbols-outlined text-primary/40">description</span>
                        </div>
                        <div class="p-sm bg-surface-container-low rounded-lg border border-outline-variant/30 min-h-[100px]">
                            <p class="text-body-md text-on-surface-variant whitespace-pre-line line-clamp-4">{{ $tmp->message_body }}</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-md pt-sm border-t border-outline-variant/30">
                        <button onclick="copyTemplateContent(this, {{ \Illuminate\Support\Js::from($tmp->message_body) }})" class="px-sm py-xs bg-surface-container text-on-surface-variant hover:bg-surface-container-high rounded-lg text-label-md font-bold transition-all flex items-center gap-xs" title="Salin ke clipboard">
                            <span class="material-symbols-outlined text-[16px]">content_copy</span>
                            <span>Salin</span>
                        </button>
                        <div class="flex gap-xs">
                            <button onclick="openEditTemplateModal({{ \Illuminate\Support\Js::from($tmp) }})" class="px-sm py-xs bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-label-md font-bold transition-all flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit</span>
                            </button>
                            <form action="{{ route('templates.destroy', $tmp->id) }}" method="POST" onsubmit="return confirm('Hapus templat pesan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-sm py-xs bg-error/10 text-error hover:bg-error/20 rounded-lg text-label-md font-bold transition-all flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 bg-surface-container-lowest border border-outline-variant p-xl rounded-xl text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[48px] text-tertiary/30 mb-sm">sticky_note_2</span>
                    <h5 class="font-bold text-body-lg text-on-surface mb-xs">Belum Ada Templat Pesan</h5>
                    <p class="text-label-md">Gunakan tombol <strong>Buat Templat</strong> untuk mendaftarkan draf tulisan pertama Anda.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL: ADD TEMPLATE -->
    <div id="addTemplateModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden border border-outline-variant">
            <div class="px-lg py-md bg-primary text-white flex justify-between items-center">
                <h4 class="font-bold text-body-lg">Buat Templat Pesan Baru</h4>
                <button onclick="toggleAddTemplateModal(false)" class="text-white/80 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('templates.store') }}" method="POST" class="p-lg space-y-md">
                @csrf
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Judul Templat</label>
                    <input type="text" name="title" required placeholder="Contoh: Promo Weekend / Invoice Tagihan"
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div>
                    <div class="flex justify-between items-center mb-xs">
                        <label class="block text-label-md font-bold text-on-surface">Isi Pesan</label>
                        <span class="text-label-sm text-on-surface-variant">Gunakan <code>[Name]</code> untuk nama kontak.</span>
                    </div>
                    <textarea name="message_body" required rows="6" placeholder="Tulis isi pesan promosi Anda di sini..."
                              class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary"></textarea>
                </div>
                <div class="pt-md flex justify-end gap-sm border-t border-outline-variant/30">
                    <button type="button" onclick="toggleAddTemplateModal(false)" class="px-md py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors">Batal</button>
                    <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-bold hover:brightness-105 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT TEMPLATE -->
    <div id="editTemplateModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden border border-outline-variant">
            <div class="px-lg py-md bg-secondary text-white flex justify-between items-center">
                <h4 class="font-bold text-body-lg">Edit Templat Pesan</h4>
                <button onclick="toggleEditTemplateModal(false)" class="text-white/80 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="editTemplateForm" method="POST" class="p-lg space-y-md">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Judul Templat</label>
                    <input type="text" name="title" id="editTemplateTitle" required
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div>
                    <div class="flex justify-between items-center mb-xs">
                        <label class="block text-label-md font-bold text-on-surface">Isi Pesan</label>
                        <span class="text-label-sm text-on-surface-variant">Gunakan <code>[Name]</code> untuk nama kontak.</span>
                    </div>
                    <textarea name="message_body" id="editTemplateBody" required rows="6"
                              class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary"></textarea>
                </div>
                <div class="pt-md flex justify-end gap-sm border-t border-outline-variant/30">
                    <button type="button" onclick="toggleEditTemplateModal(false)" class="px-md py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors">Batal</button>
                    <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-bold hover:brightness-105 transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleAddTemplateModal(show) {
            document.getElementById('addTemplateModal').classList.toggle('hidden', !show);
        }

        function toggleEditTemplateModal(show) {
            document.getElementById('editTemplateModal').classList.toggle('hidden', !show);
        }

        function openEditTemplateModal(template) {
            document.getElementById('editTemplateTitle').value = template.title;
            document.getElementById('editTemplateBody').value = template.message_body;
            
            const form = document.getElementById('editTemplateForm');
            form.action = `/templates/${template.id}`;
            
            toggleEditTemplateModal(true);
        }

        function copyTemplateContent(button, content) {
            navigator.clipboard.writeText(content).then(() => {
                const icon = button.querySelector('.material-symbols-outlined');
                const text = button.querySelector('span:last-child');
                const originalIcon = icon.textContent;
                const originalText = text.textContent;
                icon.textContent = 'check';
                text.textContent = 'Tersalin!';
                button.classList.add('text-primary');
                setTimeout(() => {
                    icon.textContent = originalIcon;
                    text.textContent = originalText;
                    button.classList.remove('text-primary');
                }, 2000);
            }).catch(() => {
                alert('Gagal menyalin konten.');
            });
        }
    </script>
@endpush
