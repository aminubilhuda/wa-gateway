@extends('layouts.app')

@section('content')
    <!-- HEADER SECTION -->
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-background">Dashboard Overview</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Real-time performance tracking for your WhatsApp outreach.</p>
        </div>
        <div class="flex gap-base">
            <button class="px-md py-2 border border-outline text-on-surface rounded-lg font-label-md hover:bg-surface-container transition-colors flex items-center gap-xs">
                <span class="material-symbols-outlined text-[18px]" data-icon="calendar_today">calendar_today</span>
                Last 7 Days
            </button>
            <button onclick="window.print()" class="px-md py-2 bg-primary text-on-primary rounded-lg font-label-md hover:brightness-110 transition-all flex items-center gap-xs shadow-sm">
                <span class="material-symbols-outlined text-[18px]" data-icon="download">download</span>
                Export PDF
            </button>
        </div>
    </div>
    
    <!-- STAT CARDS (TOP ROW) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <div class="bento-card p-lg flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="font-label-md text-on-surface-variant uppercase tracking-wider">Total Sent</span>
                <span class="material-symbols-outlined text-primary" data-icon="outbox">outbox</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display">{{ number_format($totalSent) }}</span>
            </div>
        </div>
        <div class="bento-card p-lg flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="font-label-md text-on-surface-variant uppercase tracking-wider">Success Rate</span>
                <span class="material-symbols-outlined text-primary-container" data-icon="check_circle" style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display">{{ $successRate }}%</span>
            </div>
        </div>
        <div class="bento-card p-lg flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="font-label-md text-on-surface-variant uppercase tracking-wider">Total Contacts</span>
                <span class="material-symbols-outlined text-blue-500" data-icon="group">group</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display">{{ number_format($totalContacts) }}</span>
            </div>
        </div>
        <div class="bento-card p-lg flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="font-label-md text-on-surface-variant uppercase tracking-wider">Active Campaigns</span>
                <span class="material-symbols-outlined text-amber-500" data-icon="campaign">campaign</span>
            </div>
            <div class="flex items-baseline gap-xs">
                <span class="font-display text-display">{{ number_format($activeCampaigns) }}</span>
            </div>
        </div>
    </div>
    
    <!-- BENTO GRID (MAIN CONTENT) -->
    <div class="grid grid-cols-12 gap-gutter">
        <!-- WEEKLY PERFORMANCE CHART -->
        <div class="col-span-12 lg:col-span-8 bento-card p-lg">
            <div class="flex justify-between items-center mb-xl">
                <h3 class="font-headline-md text-headline-md">Weekly Campaign Performance</h3>
                <div class="flex gap-md">
                    <div class="flex items-center gap-xs">
                        <div class="w-3 h-3 rounded-full bg-primary"></div>
                        <span class="font-label-md text-on-surface-variant">Sent (Success)</span>
                    </div>
                    <div class="flex items-center gap-xs">
                        <div class="w-3 h-3 rounded-full bg-[#EF4444]"></div>
                        <span class="font-label-md text-on-surface-variant">Failed</span>
                    </div>
                </div>
            </div>
            <div class="h-64 flex items-end justify-between gap-md px-md border-b border-outline-variant pb-base">
                <!-- Bar Items -->
                @foreach($chartData as $data)
                <div class="flex flex-col items-center gap-base flex-1">
                    <div class="w-full flex gap-1 items-end justify-center">
                        <div class="w-4 bg-primary rounded-t-sm chart-bar transition-all duration-500 ease-out" style="height: {{ $data['sent_height'] }}%;" title="Sent: {{ $data['sent'] }}"></div>
                        <div class="w-4 bg-[#EF4444] rounded-t-sm chart-bar transition-all duration-500 ease-out" style="height: {{ $data['failed_height'] }}%;" title="Failed: {{ $data['failed'] }}"></div>
                    </div>
                    <span class="font-label-sm text-on-surface-variant">{{ $data['day'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- SYSTEM STATUS -->
        <div class="col-span-12 lg:col-span-4 bento-card p-lg bg-surface-container">
            <h3 class="font-headline-md text-headline-md mb-xl">System Status</h3>
            <div class="space-y-lg">
                <div class="flex items-center gap-md p-md bg-white rounded-lg border border-outline-variant">
                    <div class="p-base {{ $isConnected ? 'bg-primary/10' : 'bg-error/10' }} rounded-lg">
                        <span class="material-symbols-outlined {{ $isConnected ? 'text-primary' : 'text-error' }}" data-icon="link" data-weight="fill">link</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-label-md text-on-surface">WhatsApp Connection</p>
                        <p class="font-body-md {{ $isConnected ? 'text-primary' : 'text-error' }} font-bold">{{ $deviceStatusMsg }}</p>
                    </div>
                    @if($isConnected)
                        <span class="material-symbols-outlined text-primary" data-icon="check_circle">check_circle</span>
                    @else
                        <span class="material-symbols-outlined text-error" data-icon="cancel">cancel</span>
                    @endif
                </div>
                <div class="flex items-center gap-md p-md bg-white rounded-lg border border-outline-variant">
                    <div class="p-base bg-secondary-container/20 rounded-lg">
                        <span class="material-symbols-outlined text-secondary" data-icon="dns">dns</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-label-md text-on-surface">WhatsApp Number</p>
                        <p class="font-body-md text-on-surface-variant">{{ $deviceNumber }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-md p-md bg-white rounded-lg border border-outline-variant">
                    <div class="p-base bg-secondary-container/20 rounded-lg">
                        <span class="material-symbols-outlined text-secondary" data-icon="speed">speed</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-label-md text-on-surface">Fonnte Quota Limit</p>
                        <p class="font-body-md text-on-surface-variant">{{ number_format((float) $deviceQuota) }} messages</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('settings') }}" class="mt-xl w-full py-2 bg-inverse-surface text-on-secondary rounded-lg font-label-md flex items-center justify-center gap-xs hover:bg-on-background transition-colors text-center">
                <span class="material-symbols-outlined text-[18px]" data-icon="sync">sync</span>
                Manage Connection
            </a>
        </div>
        
        <!-- RECENT ACTIVITIES -->
        <div class="col-span-12 bento-card p-lg">
            <div class="flex justify-between items-center mb-lg">
                <h3 class="font-headline-md text-headline-md">Recent Activities</h3>
                <a class="text-primary font-label-md hover:underline" href="{{ route('reports') }}">View All Broadcasts</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="p-md font-label-md text-on-surface-variant">Campaign Name</th>
                            <th class="p-md font-label-md text-on-surface-variant">Recipient</th>
                            <th class="p-md font-label-md text-on-surface-variant">Status</th>
                            <th class="p-md font-label-md text-on-surface-variant">Timestamp</th>
                            <th class="p-md font-label-md text-on-surface-variant text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($recentLogs as $log)
                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                            <td class="p-md font-body-md font-semibold">{{ $log->campaign->name ?? 'Direct Message' }}</td>
                            <td class="p-md font-body-md">{{ $log->contact->phone_number ?? '-' }}</td>
                            <td class="p-md">
                                @if($log->status == 'sent')
                                    <span class="status-chip bg-[#D1FAE5] text-[#065F46]">Sent</span>
                                @elseif($log->status == 'pending')
                                    <span class="status-chip bg-[#FEF3C7] text-[#92400E]">Pending</span>
                                @else
                                    <span class="status-chip bg-[#FEE2E2] text-[#991B1B]">Failed</span>
                                @endif
                            </td>
                            <td class="p-md font-label-md text-on-surface-variant">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="p-md text-right">
                                <button class="material-symbols-outlined text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity" data-icon="more_vert">more_vert</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-md text-center text-on-surface-variant">Belum ada aktivitas terbaru</td>
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
