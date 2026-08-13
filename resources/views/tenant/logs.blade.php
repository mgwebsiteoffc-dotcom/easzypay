@extends('tenant.layout')
@section('title', 'Transaction Logs')
@section('content')

<div class="stats">
    <div class="stat"><div class="stat-label">Total</div><div class="stat-val">{{ number_format($stats['total']) }}</div></div>
    <div class="stat success"><div class="stat-label">Completed</div><div class="stat-val">{{ number_format($stats['completed']) }}</div></div>
    <div class="stat warning"><div class="stat-label">Pending sync</div><div class="stat-val">{{ number_format($stats['paid']) }}</div></div>
    <div class="stat"><div class="stat-label">In progress</div><div class="stat-val">{{ number_format($stats['pending']) }}</div></div>
    <div class="stat danger"><div class="stat-label">Failed</div><div class="stat-val">{{ number_format($stats['failed']) }}</div></div>
    <div class="stat"><div class="stat-label">Expired</div><div class="stat-val">{{ number_format($stats['expired']) }}</div></div>
</div>

<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Logs</div>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <input type="text" name="search" placeholder="Email, order, PI…" value="{{ request('search') }}" style="padding:8px 12px;border:1px solid var(--line);border-radius:999px;font-size:13px;outline:none;min-width:180px;background:#fff;">
            <select name="status" style="padding:8px 12px;border:1px solid var(--line);border-radius:999px;font-size:13px;background:#fff;">
                <option value="">All status</option>
                <option value="completed" {{ request('status')==='completed'?'selected':'' }}>Completed</option>
                <option value="paid" {{ request('status')==='paid'?'selected':'' }}>Pending sync</option>
                <option value="payment_created" {{ request('status')==='payment_created'?'selected':'' }}>At checkout</option>
                <option value="pending" {{ request('status')==='pending'?'selected':'' }}>Pending</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Failed</option>
                <option value="expired" {{ request('status')==='expired'?'selected':'' }}>Expired</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}" style="padding:8px 12px;border:1px solid var(--line);border-radius:999px;font-size:13px;background:#fff;">
            <button type="submit" class="btn btn-sm">Filter</button>
            @if(request()->anyFilled(['search','status','date']))
            <a href="{{ route('tenant.logs') }}" class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Session</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Stripe</th>
                <th>Shopify</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td><code style="font-size:12px;">{{ substr($log->session_id, 0, 12) }}…</code></td>
                <td>
                    <div>{{ $log->customer_email ?? '—' }}</div>
                    @if($log->customer_first_name)
                    <div style="font-size:12px;color:var(--muted);">{{ $log->customer_first_name }} {{ $log->customer_last_name }}</div>
                    @endif
                </td>
                <td>
                    <div style="font-weight:700;">{{ $log->formatted_total }}</div>
                    @if($log->charged_currency && $log->charged_currency !== $log->currency)
                    <div style="font-size:11px;color:var(--muted);">{{ $log->currency }}@if($log->exchange_rate) · {{ number_format($log->exchange_rate, 4) }}@endif</div>
                    @endif
                </td>
                <td style="font-size:13px;">
                    @if($log->wallet_type === 'apple_pay') Apple Pay
                    @elseif($log->wallet_type === 'google_pay') Google Pay
                    @elseif($log->wallet_type === 'link') Link
                    @elseif($log->card_brand) {{ strtoupper($log->card_brand) }} ···· {{ $log->card_last4 }}
                    @else <span style="color:var(--muted);">—</span>
                    @endif
                </td>
                <td>
                    @if($log->stripe_payment_intent_id)
                    <code style="font-size:11px;">{{ substr($log->stripe_payment_intent_id, 0, 16) }}…</code>
                    @else
                    <span style="color:var(--muted);">—</span>
                    @endif
                </td>
                <td>
                    @if($log->shopify_order_number)
                    <strong>{{ $log->shopify_order_number }}</strong>
                    @else
                    <span style="color:var(--muted);font-size:12px;">Not synced</span>
                    @endif
                </td>
                <td>
                    @switch($log->status)
                        @case('completed') @case('shopify_order_created')
                            <span class="badge badge-success">Done</span> @break
                        @case('paid')
                            <span class="badge badge-warning">Sync</span> @break
                        @case('payment_created')
                            <span class="badge badge-info">Checkout</span> @break
                        @case('failed')
                            <span class="badge badge-danger">Failed</span> @break
                        @default
                            <span class="badge badge-gray">{{ ucfirst($log->status) }}</span>
                    @endswitch
                </td>
                <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                    {{ $log->created_at->format('M j, Y') }}<br>{{ $log->created_at->format('g:i a') }}
                </td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('tenant.logs.detail', $log->session_id) }}" class="btn btn-sm">View</a>
                    @if($log->status === 'paid' && !$log->shopify_order_id)
                    <form method="POST" action="{{ route('tenant.sync.order', $log->session_id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-ghost">Sync</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:40px;">No transactions found</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())
    <div style="padding:16px;text-align:center;">{{ $logs->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
