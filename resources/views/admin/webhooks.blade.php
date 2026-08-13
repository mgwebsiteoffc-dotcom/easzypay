@extends('admin.layout')

@section('page-title', 'Webhooks')

@section('content')

<div class="qp-table-wrap">
    <div class="qp-table-head">
        <div class="qp-table-title">📡 Webhook Logs</div>
        <form method="GET" class="filters">
            <select name="source" class="filter-input">
                <option value="">All Sources</option>
                <option value="stripe"  {{ request('source') === 'stripe'  ? 'selected' : '' }}>Stripe</option>
                <option value="shopify" {{ request('source') === 'shopify' ? 'selected' : '' }}>Shopify</option>
            </select>
            <select name="status" class="filter-input">
                <option value="">All Statuses</option>
                <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Processed</option>
                <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Failed</option>
                <option value="received"  {{ request('status') === 'received'  ? 'selected' : '' }}>Received</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Source</th>
                <th>Event Type</th>
                <th>Event ID</th>
                <th>Status</th>
                <th>Attempts</th>
                <th>Error</th>
                <th>Received</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($webhooks as $webhook)
            <tr>
                <td>
                    <span class="badge {{ $webhook->source === 'stripe' ? 'badge-purple' : 'badge-info' }}">
                        {{ ucfirst($webhook->source) }}
                    </span>
                </td>
                <td style="font-family:monospace;font-size:12px;">{{ $webhook->event_type }}</td>
                <td style="font-family:monospace;font-size:11px;color:#667eea;">
                    {{ substr($webhook->event_id ?? '—', 0, 20) }}
                </td>
                <td>
                    @switch($webhook->status)
                        @case('processed')
                            <span class="badge badge-success">✅ Processed</span>
                            @break
                        @case('failed')
                            <span class="badge badge-danger">❌ Failed</span>
                            @break
                        @case('received')
                            <span class="badge badge-warning">⏳ Received</span>
                            @break
                        @default
                            <span class="badge badge-gray">{{ $webhook->status }}</span>
                    @endswitch
                </td>
                <td style="text-align:center;">{{ $webhook->attempts }}</td>
                <td style="font-size:12px;color:#ef4444;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $webhook->error ?? '—' }}
                </td>
                <td style="font-size:12px;color:#9ca3af;">
                    {{ $webhook->created_at->format('M j, g:i a') }}
                </td>
                <td>
                    @if($webhook->status === 'failed')
                    <form method="POST" action="{{ route('admin.webhooks.retry', $webhook->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Retry</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;color:#9ca3af;padding:40px;">
                    No webhook logs found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">
        {{ $webhooks->withQueryString()->links('admin.pagination') }}
    </div>
</div>

@endsection