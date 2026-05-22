@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto space-y-3 sm:space-y-lg">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-1 sm:space-x-xs text-on-surface-variant font-label-md text-label-md mb-2 sm:mb-lg text-xs sm:text-sm">
            <a class="hover:text-primary" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="material-symbols-outlined text-[12px] sm:text-[14px]">chevron_right</span>
            <span class="text-primary font-bold">Blacklist Numbers</span>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-3 sm:mb-xl">
            <div>
                <h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface mb-1 sm:mb-xs">Blacklist / Daftar Hitam</h2>
                <p class="text-sm sm:text-body-md text-body-md text-on-surface-variant max-w-2xl">
                    Nomor di daftar hitam tidak akan dikirim pesan kampanye.
                </p>
            </div>
            <button onclick="toggleAddBlacklistModal(true)" class="bg-error text-on-error px-3 sm:px-lg py-1.5 sm:py-sm rounded-lg font-bold hover:brightness-110 active:scale-95 transition-all flex items-center gap-1 sm:gap-xs shadow-md text-xs sm:text-sm w-full sm:w-auto justify-center">
                <span class="material-symbols-outlined text-[16px] sm:text-[20px]">block</span>
                <span>Cekal Nomor</span>
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-lg py-2 sm:py-md rounded-xl mb-3 sm:mb-lg text-xs sm:text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter & Search Toolbar -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3 sm:p-lg flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 sm:gap-md">
            <form action="{{ route('blacklist') }}" method="GET" class="w-full sm:w-auto sm:flex-1 max-w-md flex gap-2 sm:gap-sm">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor atau alasan..."
                       class="flex-1 bg-surface-container-low border border-outline-variant px-2 sm:px-md py-1.5 sm:py-sm rounded-lg focus:outline-none focus:border-primary text-xs sm:text-body-md">
                <button type="submit" class="bg-secondary-container text-on-secondary-container px-3 sm:px-lg py-1.5 sm:py-sm rounded-lg font-bold hover:bg-secondary-container/80 transition-all flex items-center gap-1 sm:gap-xs text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[16px] sm:text-[20px]">search</span>
                    <span class="hidden xs:inline">Cari</span>
                </button>
            </form>
            @if(request('search'))
                <a href="{{ route('blacklist') }}" class="text-xs sm:text-label-md text-primary font-bold hover:underline text-center sm:text-left">Reset Search</a>
            @endif
        </div>

        <!-- Table Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full text-left border-collapse min-w-[400px]">
                    <tbody class="divide-y divide-outline-variant">
                        <tr class="bg-surface-container-low text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Nomor</td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Alasan</td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium">Tanggal</td>
                            <td class="px-2 sm:px-lg py-1.5 sm:py-md font-medium text-right">Tindakan</td>
                        </tr>
                        @forelse($blacklists as $bl)
                            <tr class="hover:bg-surface-container/20 transition-colors">
                                <td class="px-2 sm:px-lg py-1.5 sm:py-md font-bold text-xs sm:text-body-md text-error">
                                    {{ $bl->phone_number }}
                                </td>
                                <td class="px-2 sm:px-lg py-1.5 sm:py-md text-xs sm:text-body-md text-on-surface-variant">
                                    {{ $bl->reason ?? 'Manual Blacklist' }}
                                </td>
                                <td class="px-2 sm:px-lg py-1.5 sm:py-md text-xs sm:text-body-md text-on-surface-variant whitespace-nowrap">
                                    {{ $bl->created_at->format('d M Y') }}
                                </td>
                                <td class="px-2 sm:px-lg py-1.5 sm:py-md text-right">
                                    <form action="{{ route('blacklist.destroy', $bl->id) }}" method="POST" onsubmit="return confirm('Hapus nomor ini dari daftar hitam?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-1.5 sm:px-sm py-0.5 sm:py-xs bg-green-50 text-green-700 hover:bg-green-100 rounded-lg text-[10px] sm:text-label-md font-bold transition-all flex items-center gap-0.5 sm:gap-xs ml-auto">
                                            <span class="material-symbols-outlined text-[14px] sm:text-[16px]">check_circle</span>
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 sm:px-lg py-2 sm:py-xl text-center text-on-surface-variant text-xs">Tidak ada nomor yang dicekal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($blacklists->hasPages())
                <div class="px-3 sm:px-lg py-2 sm:py-md bg-surface-container-low border-t border-outline-variant">
                    {{ $blacklists->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL: ADD BLACKLIST -->
    <div id="addBlacklistModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-outline-variant">
            <div class="px-lg py-md bg-error text-white flex justify-between items-center">
                <h4 class="font-bold text-body-lg flex items-center gap-xs">
                    <span class="material-symbols-outlined">block</span>
                    <span>Cekal Nomor WhatsApp</span>
                </h4>
                <button onclick="toggleAddBlacklistModal(false)" class="text-white/80 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('blacklist.store') }}" method="POST" class="p-lg space-y-md">
                @csrf
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Nomor Telepon</label>
                    <input type="text" name="phone_number" required placeholder="Contoh: 6281234567890"
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                    <span class="text-label-sm text-on-surface-variant mt-xs block">Format nomor diawali dengan 62 atau 0 (misal: 62812xxx atau 0812xxx).</span>
                </div>
                <div>
                    <label class="block text-label-md font-bold text-on-surface mb-xs">Alasan Dicekal (Opsional)</label>
                    <input type="text" name="reason" placeholder="Contoh: Permintaan pelanggan via WA / Spam"
                           class="w-full bg-surface-container-low border border-outline-variant p-sm rounded-lg text-body-md focus:outline-none focus:border-primary">
                </div>
                <div class="pt-md flex justify-end gap-sm border-t border-outline-variant/30">
                    <button type="button" onclick="toggleAddBlacklistModal(false)" class="px-md py-sm border border-outline text-on-surface rounded-lg font-bold hover:bg-surface-container transition-colors">Batal</button>
                    <button type="submit" class="px-md py-sm bg-error text-on-error rounded-lg font-bold hover:brightness-105 transition-all">Cekal Nomor</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleAddBlacklistModal(show) {
            document.getElementById('addBlacklistModal').classList.toggle('hidden', !show);
        }
    </script>
@endpush
