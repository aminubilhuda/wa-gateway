@extends('layouts.app')

@section('content')
    <!-- HEADER SECTION -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-3 sm:gap-base">
        <div>
            <h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-background">Dashboard Overview</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Real-time performance tracking for your WhatsApp outreach.</p>
        </div>
        <div class="flex gap-2 sm:gap-base w-full sm:w-auto">
            <button class="flex-1 sm:flex-none px-3 sm:px-md py-2 border border-outline text-on-surface rounded-lg font-label-md hover:bg-surface-container transition-colors flex items-center justify-center gap-xs text-sm">
                <span class="material-symbols-outlined text-[18px]" data-icon="calendar_today">calendar_today</span>
                <span class="hidden xs:inline">Last 7 Days</span>
            </button>
            <button onclick="window.print()" class="flex-1 sm:flex-none px-3 sm:px-md py-2 bg-primary text-on-primary rounded-lg font-label-md hover:brightness-110 transition-all flex items-center justify-center gap-xs shadow-sm text-sm">
                <span class="material-symbols-outlined text-[18px]" data-icon="download">download</span>
                <span class="hidden xs:inline">Export PDF</span>
            </button>
        </div>
    </div>
    
    <!-- STAT CARDS (TOP ROW) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-gutter">
        <div class="bento-card p-3 sm:p-md lg:p-lg flex flex-col justify-between h-24 sm:h-28 lg:h-32">
            <div class="flex justify-between items-start">
                <span class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Total Sent</span>
                <span class="material-symbols-outlined text-primary text-[20px] sm:text-[24px]" data-icon="outbox">outbox</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display text-xl sm:text-2xl lg:text-display">{{ number_format($totalSent) }}</span>
            </div>
        </div>
        <div class="bento-card p-3 sm:p-md lg:p-lg flex flex-col justify-between h-24 sm:h-28 lg:h-32">
            <div class="flex justify-between items-start">
                <span class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Success Rate</span>
                <span class="material-symbols-outlined text-primary-container text-[20px] sm:text-[24px]" data-icon="check_circle" style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display text-xl sm:text-2xl lg:text-display">{{ $successRate }}%</span>
            </div>
        </div>
        <div class="bento-card p-3 sm:p-md lg:p-lg flex flex-col justify-between h-24 sm:h-28 lg:h-32">
            <div class="flex justify-between items-start">
                <span class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Total Contacts</span>
                <span class="material-symbols-outlined text-blue-500 text-[20px] sm:text-[24px]" data-icon="group">group</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display text-xl sm:text-2xl lg:text-display">{{ number_format($totalContacts) }}</span>
            </div>
        </div>
        <div class="bento-card p-3 sm:p-md lg:p-lg flex flex-col justify-between h-24 sm:h-28 lg:h-32">
            <div class="flex justify-between items-start">
                <span class="text-[10px] sm:text-label-md text-on-surface-variant uppercase tracking-wider">Active Campaigns</span>
                <span class="material-symbols-outlined text-amber-500 text-[20px] sm:text-[24px]" data-icon="campaign">campaign</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display text-xl sm:text-2xl lg:text-display">{{ number_format($activeCampaigns) }}</span>
            </div>
        </div>
    </div>
    
    <!-- BENTO GRID (MAIN CONTENT) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-gutter">
        <!-- WEEKLY PERFORMANCE CHART -->
        <div class="lg:col-span-8 bento-card p-3 sm:p-md lg:p-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0 mb-3 sm:mb-xl">
                <h3 class="font-headline-md text-headline-md text-sm sm:text-base lg:text-headline-md">Weekly Campaign Performance</h3>
                <div class="flex gap-2 sm:gap-md">
                    <div class="flex items-center gap-xs">
                        <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                        <span class="text-[10px] sm:text-label-md text-on-surface-variant">Sent</span>
                    </div>
                    <div class="flex items-center gap-xs">
                        <div class="w-2.5 h-2.5 rounded-full bg-[#EF4444]"></div>
                        <span class="text-[10px] sm:text-label-md text-on-surface-variant">Failed</span>
                    </div>
                </div>
            </div>
            <div class="h-40 sm:h-48 lg:h-64 flex items-end justify-between gap-1 sm:gap-md px-1 sm:px-md border-b border-outline-variant pb-base">
                @foreach($chartData as $data)
                <div class="flex flex-col items-center gap-1 sm:gap-base flex-1 min-w-0">
                    <div class="w-full flex gap-0.5 sm:gap-1 items-end justify-center">
                        <div class="w-2 sm:w-3 lg:w-4 bg-primary rounded-t-sm chart-bar transition-all duration-500 ease-out" style="height: {{ $data['sent_height'] }}%;" title="Sent: {{ $data['sent'] }}"></div>
                        <div class="w-2 sm:w-3 lg:w-4 bg-[#EF4444] rounded-t-sm chart-bar transition-all duration-500 ease-out" style="height: {{ $data['failed_height'] }}%;" title="Failed: {{ $data['failed'] }}"></div>
                    </div>
                    <span class="text-[9px] sm:text-label-sm text-on-surface-variant">{{ $data['day'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- SYSTEM STATUS -->
        <div class="lg:col-span-4 bento-card p-3 sm:p-md lg:p-lg bg-surface-container">
            <h3 class="font-headline-md text-headline-md text-sm sm:text-base lg:text-headline-md mb-3 sm:mb-xl">System Status</h3>
            <div class="space-y-3 sm:space-y-lg">
                <div class="flex items-center gap-2 sm:gap-md p-2 sm:p-md bg-white rounded-lg border border-outline-variant">
                    <div class="p-1.5 sm:p-base {{ $connectedDevices > 0 ? 'bg-primary/10' : 'bg-error/10' }} rounded-lg">
                        <span class="material-symbols-outlined {{ $connectedDevices > 0 ? 'text-primary' : 'text-error' }} text-[20px] sm:text-[24px]" data-icon="link" data-weight="fill">link</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] sm:text-label-md text-on-surface">WhatsApp Devices</p>
                        <p class="text-xs sm:text-body-md {{ $connectedDevices > 0 ? 'text-primary' : 'text-error' }} font-bold">{{ $connectedDevices }}/{{ $totalDevices }} Connected</p>
                    </div>
                    @if($connectedDevices > 0)
                        <span class="material-symbols-outlined text-primary text-[20px] sm:text-[24px]" data-icon="check_circle">check_circle</span>
                    @else
                        <span class="material-symbols-outlined text-error text-[20px] sm:text-[24px]" data-icon="cancel">cancel</span>
                    @endif
                </div>

                @forelse($devices as $device)
                <div class="flex items-center gap-2 sm:gap-md p-2 sm:p-md bg-white rounded-lg border border-outline-variant">
                    <div class="p-1.5 sm:p-base {{ $device->status === 'connected' ? 'bg-primary/10' : 'bg-outline-variant/30' }} rounded-lg">
                        <span class="material-symbols-outlined {{ $device->status === 'connected' ? 'text-primary' : 'text-on-surface-variant' }} text-[20px] sm:text-[24px]" data-icon="dns" data-weight="fill">dns</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] sm:text-label-md text-on-surface truncate">{{ $device->name }}</p>
                        <p class="text-xs sm:text-body-md text-on-surface-variant flex items-center gap-xs">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full {{ $device->status === 'connected' ? 'bg-green-500' : 'bg-outline-variant' }}"></span>
                            {{ $device->status === 'connected' ? 'Connected' : 'Disconnected' }}
                            @if($device->phone_number)
                                <span class="truncate hidden sm:inline">&middot; {{ $device->phone_number }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                @empty
                <div class="flex items-center gap-md p-md bg-white rounded-lg border border-outline-variant">
                    <div class="flex-1 text-center text-on-surface-variant text-label-md">
                        Belum ada perangkat. <a href="{{ route('settings') }}" class="text-primary hover:underline font-bold">Tambah perangkat</a>
                    </div>
                </div>
                @endforelse
            </div>
            <a href="{{ route('settings') }}" class="mt-3 sm:mt-xl w-full py-1.5 sm:py-2 bg-inverse-surface text-on-secondary rounded-lg font-label-md flex items-center justify-center gap-xs hover:bg-on-background transition-colors text-center text-xs sm:text-sm">
                <span class="material-symbols-outlined text-[16px] sm:text-[18px]" data-icon="settings">settings</span>
                Manage Connections
            </a>
        </div>
        
        <!-- RECENT ACTIVITIES -->
        <div class="lg:col-span-12 bento-card p-3 sm:p-md lg:p-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0 mb-3 sm:mb-lg">
                <h3 class="font-headline-md text-headline-md text-sm sm:text-base lg:text-headline-md">Recent Activities</h3>
                <a class="text-primary font-label-md hover:underline text-xs sm:text-sm" href="{{ route('reports') }}">View All Broadcasts</a>
            </div>
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full text-left min-w-[500px]">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md text-on-surface-variant">Campaign Name</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md text-on-surface-variant">Recipient</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md text-on-surface-variant">Status</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md text-on-surface-variant">Timestamp</th>
                            <th class="p-2 sm:p-md text-[10px] sm:text-label-md text-on-surface-variant text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($recentLogs as $log)
                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                            <td class="p-2 sm:p-md text-xs sm:text-body-md font-semibold truncate max-w-[120px] sm:max-w-none">{{ $log->campaign->name ?? 'Direct Message' }}</td>
                            <td class="p-2 sm:p-md text-xs sm:text-body-md">{{ $log->contact->phone_number ?? '-' }}</td>
                            <td class="p-2 sm:p-md">
                                @if($log->status == 'sent')
                                    <span class="status-chip text-[9px] sm:text-[11px] bg-[#D1FAE5] text-[#065F46]">Sent</span>
                                @elseif($log->status == 'pending')
                                    <span class="status-chip text-[9px] sm:text-[11px] bg-[#FEF3C7] text-[#92400E]">Pending</span>
                                @else
                                    <span class="status-chip text-[9px] sm:text-[11px] bg-[#FEE2E2] text-[#991B1B]">Failed</span>
                                @endif
                            </td>
                            <td class="p-2 sm:p-md text-[10px] sm:text-label-md text-on-surface-variant whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="p-2 sm:p-md text-right">
                                <button class="material-symbols-outlined text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity text-[18px] sm:text-[24px]" data-icon="more_vert">more_vert</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-2 sm:p-md text-center text-on-surface-variant text-xs sm:text-sm">Belum ada aktivitas terbaru</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Simple micro-interaction for bars
        window.addEventListener('DOMContentLoaded', () => {
            const bars = document.querySelectorAll('.chart-bar');
            bars.forEach(bar => {
                const height = bar.style.height;
                bar.style.height = '0';
                setTimeout(() => {
                    bar.style.height = height;
                }, 100);
            });
        });
    </script>
@endpush
