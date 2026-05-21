@extends('layouts.app')

@section('content')
    <!-- Content Canvas -->
    <div class="p-lg space-y-lg">
        <!-- Header & Export Action -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-on-background">History &amp; Reports</h2>
                <p class="text-body-md text-on-surface-variant">Real-time analysis of your WhatsApp broadcast performance.</p>
            </div>
            <div class="flex gap-base">
                <a href="{{ route('reports.export', request()->query()) }}" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary font-label-md rounded-lg hover:brightness-110 shadow-lg shadow-primary/20 transition-all active:scale-95">
                    <span class="material-symbols-outlined" data-icon="file_download">file_download</span>
                    Export CSV
                </a>
            </div>
        </div>

        <!-- Bento Filter Bar -->
        <form action="{{ route('reports') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-md w-full">
            <div class="md:col-span-1 glass-card p-md rounded-xl space-y-xs bg-white/80 backdrop-blur-md border border-outline-variant">
                <label class="text-label-sm text-outline uppercase tracking-wider">Date Range</label>
                <div class="flex items-center gap-2 text-on-surface">
                    <span class="material-symbols-outlined text-primary" data-icon="calendar_today">calendar_today</span>
                    <select name="date_range" onchange="this.form.submit()" class="bg-transparent border-none focus:ring-0 font-label-md w-full cursor-pointer">
                        <option value="">Semua Waktu</option>
                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="7_days" {{ request('date_range') == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="30_days" {{ request('date_range') == '30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                        <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    </select>
                </div>
            </div>
            <div class="md:col-span-1 glass-card p-md rounded-xl space-y-xs bg-white/80 backdrop-blur-md border border-outline-variant">
                <label class="text-label-sm text-outline uppercase tracking-wider">Campaign</label>
                <div class="flex items-center gap-2 text-on-surface">
                    <span class="material-symbols-outlined text-primary" data-icon="campaign">campaign</span>
                    <select name="campaign_id" onchange="this.form.submit()" class="bg-transparent border-none focus:ring-0 font-label-md w-full cursor-pointer">
                        <option value="">Semua Kampanye</option>
                        @foreach($campaigns as $camp)
                            <option value="{{ $camp->id }}" {{ request('campaign_id') == $camp->id ? 'selected' : '' }}>{{ $camp->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="md:col-span-1 glass-card p-md rounded-xl space-y-xs bg-white/80 backdrop-blur-md border border-outline-variant">
                <label class="text-label-sm text-outline uppercase tracking-wider">Status</label>
                <div class="flex items-center gap-2 text-on-surface">
                    <span class="material-symbols-outlined text-primary" data-icon="filter_list">filter_list</span>
                    <select name="status" onchange="this.form.submit()" class="bg-transparent border-none focus:ring-0 font-label-md w-full cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Success (Sent)</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
            </div>
            <div class="md:col-span-1 flex items-end">
                <a href="{{ route('reports') }}" class="w-full glass-card p-md rounded-xl bg-white/80 backdrop-blur-md border border-outline-variant text-on-surface font-label-md hover:bg-secondary-container/50 transition-all flex items-center justify-center gap-2" style="height: 60px;">
                    <span class="material-symbols-outlined text-primary" data-icon="refresh">refresh</span>
                    Reset Filters
                </a>
            </div>
        </form>

        <!-- Stats Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
            <div class="bg-white border border-outline-variant p-lg rounded-xl flex items-center gap-lg">
                <div class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined" data-icon="done_all" data-weight="fill" style="font-variation-settings: 'FILL' 1;">done_all</span>
                </div>
                <div>
                    <p class="text-label-sm text-outline">Total Logs</p>
                    <p class="font-headline-md text-headline-md text-on-background">{{ number_format($totalLogs) }}</p>
                </div>
            </div>
            <div class="bg-white border border-outline-variant p-lg rounded-xl flex items-center gap-lg">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                    <span class="material-symbols-outlined" data-icon="check_circle">check_circle</span>
                </div>
                <div>
                    <p class="text-label-sm text-outline">Success Rate</p>
                    <p class="font-headline-md text-headline-md text-on-background">{{ $successRate }}%</p>
                    <span class="text-label-sm text-outline">{{ number_format($successCount) }} Berhasil</span>
                </div>
            </div>
            <div class="bg-white border border-outline-variant p-lg rounded-xl flex items-center gap-lg">
                <div class="w-12 h-12 rounded-full bg-error-container/40 flex items-center justify-center text-error">
                    <span class="material-symbols-outlined" data-icon="warning">warning</span>
                </div>
                <div>
                    <p class="text-label-sm text-outline">Failed Rate</p>
                    <p class="font-headline-md text-headline-md text-on-background">{{ $failedRate }}%</p>
                    <span class="text-label-sm text-outline">{{ number_format($failedCount) }} Gagal</span>
                </div>
            </div>
        </div>

        <!-- Detailed Log Table -->
        <div class="bg-white border border-outline-variant rounded-xl overflow-hidden">
            <div class="p-md bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-label-md text-on-surface uppercase tracking-wider">Transmission Log</h3>
                <div class="flex gap-2">
                    <button class="p-1 hover:bg-surface-container rounded transition-colors text-outline">
                        <span class="material-symbols-outlined text-[20px]" data-icon="more_vert">more_vert</span>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-label-sm text-outline border-b border-outline-variant">
                            <th class="px-lg py-4 font-semibold uppercase tracking-wider">Timestamp</th>
                            <th class="px-lg py-4 font-semibold uppercase tracking-wider">Campaign Name</th>
                            <th class="px-lg py-4 font-semibold uppercase tracking-wider">Recipient</th>
                            <th class="px-lg py-4 font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-lg py-4 font-semibold uppercase tracking-wider">Read Receipt</th>
                            <th class="px-lg py-4 font-semibold uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($logs as $log)
                        <tr class="hover:bg-background/50 transition-colors">
                            <td class="px-lg py-md whitespace-nowrap">
                                <p class="text-body-md text-on-surface">{{ $log->created_at->format('M d, Y') }}</p>
                                <p class="text-label-sm text-outline">{{ $log->created_at->format('h:i A') }}</p>
                            </td>
                            <td class="px-lg py-md font-label-md text-on-surface">{{ $log->campaign->name ?? 'Direct Message' }}</td>
                            <td class="px-lg py-md text-body-md text-on-surface">{{ $log->contact->phone_number ?? '-' }}</td>
                            <td class="px-lg py-md">
                                @if($log->status == 'sent')
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-container/20 text-on-primary-container rounded-full text-label-sm font-bold">
                                    <span class="material-symbols-outlined text-[16px]" data-icon="check_circle">check_circle</span>
                                    Success
                                </div>
                                @elseif($log->status == 'pending')
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-label-sm font-bold">
                                    <span class="material-symbols-outlined text-[16px]" data-icon="schedule">schedule</span>
                                    Pending
                                </div>
                                @else
                                <div class="group relative inline-flex items-center gap-1.5 px-3 py-1 bg-error-container/40 text-error rounded-full text-label-sm font-bold cursor-help">
                                    <span class="material-symbols-outlined text-[16px]" data-icon="error">error</span>
                                    Failed
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-48 p-2 bg-inverse-surface text-on-secondary text-[10px] rounded shadow-xl z-10 font-normal">
                                        Error during transmission.
                                    </div>
                                </div>
                                @endif
                            </td>
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-2 text-outline/50">
                                    <span class="material-symbols-outlined" data-icon="done_all">done_all</span>
                                    <span class="text-label-sm">--</span>
                                </div>
                            </td>
                            <td class="px-lg py-md text-right">
                                <button onclick="showLogDetails({{ json_encode($log) }})" class="text-outline hover:text-primary transition-colors" title="View Details"><span class="material-symbols-outlined" data-icon="info">info</span></button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-lg py-md text-center text-on-surface-variant">Belum ada riwayat pengiriman pesan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-lg py-md bg-surface-container-low border-t border-outline-variant flex items-center justify-between">
                <span class="text-label-sm text-outline">
                    Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results
                </span>
                @if ($logs->hasPages())
                <div class="flex gap-1">
                    {{-- Previous Page Link --}}
                    @if ($logs->onFirstPage())
                        <button class="p-2 border border-outline-variant rounded text-outline opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[18px]" data-icon="chevron_left">chevron_left</span>
                        </button>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="p-2 border border-outline-variant rounded hover:bg-white text-outline flex items-center">
                            <span class="material-symbols-outlined text-[18px]" data-icon="chevron_left">chevron_left</span>
                        </a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach ($logs->getUrlRange(max(1, $logs->currentPage() - 2), min($logs->lastPage(), $logs->currentPage() + 2)) as $page => $url)
                        @if ($page == $logs->currentPage())
                            <button class="px-3 py-1 border border-primary bg-primary text-on-primary rounded text-label-sm font-bold">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1 border border-outline-variant rounded hover:bg-white text-on-surface text-label-sm flex items-center">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="p-2 border border-outline-variant rounded hover:bg-white text-outline flex items-center">
                            <span class="material-symbols-outlined text-[18px]" data-icon="chevron_right">chevron_right</span>
                        </a>
                    @else
                        <button class="p-2 border border-outline-variant rounded text-outline opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[18px]" data-icon="chevron_right">chevron_right</span>
                        </button>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Analysis Cards (Decorative/Extra Detail) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg pb-xl">
            <div class="bg-white border border-outline-variant rounded-xl p-lg space-y-md">
                <h4 class="font-headline-md text-headline-md text-on-background">Delivery Timeline (Today)</h4>
                <div class="h-48 w-full bg-surface-container-low rounded-lg relative overflow-hidden flex items-end px-md gap-2">
                    @forelse($hourlyData as $hour)
                    <div class="flex-1 {{ $hour['count'] > 0 ? 'bg-primary' : 'bg-primary/20' }} rounded-t-sm transition-all hover:bg-primary/40 h-[{{ max($hour['height'], 5) }}%]" title="{{ $hour['hour'] }}: {{ $hour['count'] }} messages"></div>
                    @empty
                    <div class="flex-1 bg-primary/20 rounded-t-sm h-[5%]"></div>
                    @endforelse
                </div>
                <div class="flex justify-between text-label-sm text-outline px-2">
                    @foreach($hourlyData as $index => $hour)
                        @if($index % 2 == 0)
                        <span>{{ $hour['hour'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="bg-white border border-outline-variant rounded-xl p-lg space-y-md">
                <h4 class="font-headline-md text-headline-md text-on-background">Transmission Status</h4>
                <div class="flex items-center justify-between gap-xl">
                    <div class="flex-1 space-y-4">
                        <div class="space-y-1">
                            <div class="flex justify-between text-label-sm">
                                <span class="text-on-surface">Success (Sent)</span>
                                <span class="text-primary font-bold">{{ $successRate }}%</span>
                            </div>
                            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: {{ $successRate }}%;"></div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-label-sm">
                                <span class="text-on-surface">Pending</span>
                                <span class="text-secondary font-bold">{{ $pendingRate }}%</span>
                            </div>
                            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                                <div class="h-full bg-secondary rounded-full" style="width: {{ $pendingRate }}%;"></div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-label-sm">
                                <span class="text-on-surface">Failed</span>
                                <span class="text-error font-bold">{{ $failedRate }}%</span>
                            </div>
                            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                                <div class="h-full bg-error rounded-full" style="width: {{ $failedRate }}%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="w-32 h-32 relative flex items-center justify-center">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" fill="none" r="16" stroke="#e5eeff" stroke-width="4"></circle>
                            <circle cx="18" cy="18" fill="none" r="16" stroke="var(--md-sys-color-primary, #006d2f)" stroke-dasharray="{{ $successRate }}, 100" stroke-width="4"></circle>
                        </svg>
                        <div class="absolute flex flex-col items-center">
                            <span class="text-headline-md font-bold text-on-background">{{ $successRate }}%</span>
                            <span class="text-[8px] text-outline uppercase">Success</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Message Log Details</h3>
                <button onclick="document.getElementById('logDetailsModal').classList.add('hidden')" type="button" class="text-on-surface-variant hover:text-error">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-lg space-y-md">
                <div class="grid grid-cols-2 gap-md">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Campaign</p>
                        <p class="font-bold text-body-md text-on-surface" id="logCampaign">-</p>
                    </div>
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Status</p>
                        <p class="font-bold text-body-md" id="logStatus">-</p>
                    </div>
                </div>
                <div>
                    <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Recipient</p>
                    <p class="font-bold text-body-md text-on-surface" id="logRecipient">-</p>
                </div>
                <div>
                    <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Message</p>
                    <div class="bg-surface-container-low p-sm rounded-lg text-body-md text-on-surface whitespace-pre-wrap max-h-48 overflow-y-auto" id="logMessage">-</div>
                </div>
                <div class="grid grid-cols-2 gap-md">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Sent At</p>
                        <p class="font-bold text-body-md text-on-surface" id="logSentAt">-</p>
                    </div>
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Created At</p>
                        <p class="font-bold text-body-md text-on-surface" id="logCreatedAt">-</p>
                    </div>
                </div>
            </div>
            <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end">
                <button type="button" onclick="document.getElementById('logDetailsModal').classList.add('hidden')" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showLogDetails(log) {
            document.getElementById('logCampaign').textContent = log.campaign ? log.campaign.name : 'Direct Message';
            document.getElementById('logStatus').textContent = log.status.charAt(0).toUpperCase() + log.status.slice(1);
            document.getElementById('logRecipient').textContent = log.contact ? (log.contact.name + ' (' + log.contact.phone_number + ')') : '-';
            document.getElementById('logMessage').textContent = log.message_body || '-';
            document.getElementById('logSentAt').textContent = log.sent_at ? new Date(log.sent_at).toLocaleString() : '-';
            document.getElementById('logCreatedAt').textContent = new Date(log.created_at).toLocaleString();

            const statusEl = document.getElementById('logStatus');
            if (log.status === 'sent') {
                statusEl.className = 'font-bold text-body-md text-primary';
            } else if (log.status === 'failed') {
                statusEl.className = 'font-bold text-body-md text-error';
            } else {
                statusEl.className = 'font-bold text-body-md text-yellow-600';
            }

            document.getElementById('logDetailsModal').classList.remove('hidden');
        }

        document.getElementById('logDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
@endpush
