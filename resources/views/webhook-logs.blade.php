@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-md mb-3 sm:mb-lg">
    <div>
        <h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-background">Webhook Logs</h2>
        <p class="text-body-md text-body-md text-on-surface-variant text-sm">Incoming webhook requests from WhatsApp gateway.</p>
    </div>
</div>

<form action="{{ route('webhook-logs') }}" method="GET" class="mb-3 sm:mb-md">
    <div class="flex gap-2 sm:gap-sm items-end">
        <div>
            <label class="block text-[10px] sm:text-label-md text-on-surface-variant mb-1">Type</label>
            <select name="type" onchange="this.form.submit()" class="bg-surface-container-low border border-outline-variant rounded-lg px-3 py-1.5 sm:py-2 text-sm">
                <option value="">All</option>
                <option value="device" {{ request('type') == 'device' ? 'selected' : '' }}>Device</option>
                <option value="message" {{ request('type') == 'message' ? 'selected' : '' }}>Message</option>
            </select>
        </div>
        <a href="{{ route('webhook-logs') }}" class="px-3 sm:px-md py-1.5 sm:py-2 border border-outline-variant rounded-lg text-sm hover:bg-surface-container-low">Reset</a>
    </div>
</form>

<div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
    <div class="overflow-x-auto overflow-y-hidden">
        <table class="w-full text-left border-collapse min-w-[500px]">
            <thead>
                <tr class="bg-surface-container-low text-[10px] sm:text-label-sm text-outline border-b border-outline-variant">
                    <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">ID</th>
                    <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Type</th>
                    <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Status</th>
                    <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider">Time</th>
                    <th class="px-2 sm:px-lg py-2 sm:py-3 font-semibold uppercase tracking-wider text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($logs as $log)
                <tr class="hover:bg-surface-container/50 transition-colors">
                    <td class="px-2 sm:px-lg py-1.5 sm:py-md text-xs font-mono">#{{ $log->id }}</td>
                    <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                        <span class="px-1.5 sm:px-sm py-0.5 bg-primary/10 text-primary rounded-full text-[9px] sm:text-label-sm font-bold">{{ ucfirst($log->type) }}</span>
                    </td>
                    <td class="px-2 sm:px-lg py-1.5 sm:py-md">
                        <span class="text-xs {{ $log->status === 'received' ? 'text-green-600' : 'text-amber-600' }}">{{ ucfirst($log->status) }}</span>
                    </td>
                    <td class="px-2 sm:px-lg py-1.5 sm:py-md text-xs text-on-surface-variant whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                    <td class="px-2 sm:px-lg py-1.5 sm:py-md text-right">
                        <button onclick="showWebhookPayload({{ $log->id }})" class="text-outline hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-2 sm:px-lg py-1.5 sm:py-md text-center text-on-surface-variant text-xs">Belum ada webhook log.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($logs->hasPages())
    <div class="p-2 sm:p-md bg-surface-container-low border-t border-outline-variant">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<div id="payloadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-base">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[80vh] overflow-y-auto">
        <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center sticky top-0 bg-white">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Webhook Payload</h3>
            <button onclick="document.getElementById('payloadModal').classList.add('hidden')" class="text-on-surface-variant hover:text-error">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-lg">
            <pre id="payloadContent" class="bg-surface-container-low p-md rounded-lg text-xs font-mono whitespace-pre-wrap overflow-x-auto max-h-[60vh]"></pre>
        </div>
        <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end">
            <button onclick="document.getElementById('payloadModal').classList.add('hidden')" class="px-md py-sm bg-primary text-white rounded-lg font-bold">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showWebhookPayload(id) {
    fetch(`/webhook-logs/${id}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('payloadContent').textContent = JSON.stringify(JSON.parse(data.payload || '{}'), null, 2);
        document.getElementById('payloadModal').classList.remove('hidden');
    });
}
</script>
@endpush
