@extends('tenant.layout')
@section('title', 'Transaction Logs')
@section('content')

<!-- Stats -->
<div class="stats">
    <div class="stat"><div class="stat-label">Total Transactions</div><div class="stat-val">{{ number_format($stats['total']) }}</div></div>
    <div class="stat success"><div class="stat-label">Completed</div><div class="stat-val">{{ number_format($stats['completed']) }}</div></div>
    <div class="stat warning"><div class="stat-label">Pending Sync</div><div class="stat-val">{{ number_format($stats['paid']) }}</div></div>
    <div class="stat"><div class="stat-label">In Progress</div><div class="stat-val">{{ number_format($stats['pending']) }}</div></div>
    <div class="stat danger"><div class="stat-label">Failed</div><div class="stat-val">{{ number_format($stats['failed']) }}</div></div>
    <div class="stat"><div class="stat-label">Expired</div><div class="stat-val">{{ number_format($stats['expired']) }}</div></div>
</div>

<div class="tw">
    <div class="tw-head">
        <div class="tw-title">📋 Transaction Logs</div>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Email, Order#, PI..." value="{{ request('search') }}" style="padding:7px 12px;border:1.5px solid var(--g200);border-radius:7px;font-size:13px;outline:none;">
            <select name="status" style="padding:7px 12px;border:1.5px solid var(--g200);border-radius:7px;font-size:13px;">
                <option value="">All Status</option>
                <option value="completed" {{ request('status')==='completed'?'selected':'' }}>✅ Completed</option>
                <option value="paid" {{ request('status')==='paid'?'selected':'' }}>⏳ Pending Sync</option>
                <option value="payment_created" {{ request('status')==='payment_created'?'selected':'' }}>💳 At Checkout</option>
                <option value="pending" {{ request('status')==='pending'?'selected':'' }}>Pending</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>❌ Failed</option>
                <option value="expired" {{ request('status')==='expired'?'selected':'' }}>Expired</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}" style="padding:7px 12px;border:1.5px solid var(--g200);border-radius:7px;font-size:13px;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->anyFilled(['search','status','date']))
            <a href="{{ route('tenant.logs') }}" class="btn btn-sm" style="background:var(--g100);color:var(--g700);">Clear</a>
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
                <th>Stripe PI</th>
                <th>Shopify Order</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>
                    <code style="font-size:11px;color:var(--p);font-family:monospace;">
                        {{ substr($log->session_id, 0, 12) }}...
                    </code>
                </td>
                <td>
                    <div>{{ $log->customer_email ?? '—' }}</div>
                    @if($log->customer_first_name)
                    <div style="font-size:11px;color:var(--g400);">{{ $log->customer_first_name }} {{ $log->customer_last_name }}</div>
                    @endif
                </td>
                <td>
                    <div style="font-weight:700;">
                        {{ $log->formatted_total }}
                    </div>
                    @if($log->charged_currency && $log->charged_currency !== $log->currency)
                    <div style="font-size:10px;color:var(--g400);">
                        Base: {{ $log->currency }}
                        @if($log->exchange_rate) · Rate: {{ number_format($log->exchange_rate, 4) }} @endif
                    </div>
                    @endif
                </td>
                <td style="font-size:12px;">
                    @if($log->wallet_type === 'apple_pay') 🍎 Apple Pay
                    @elseif($log->wallet_type === 'google_pay') G Google Pay
                    @elseif($log->wallet_type === 'link') 🔗 Link
                    @elseif($log->card_brand) 💳 {{ strtoupper($log->card_brand) }} •••• {{ $log->card_last4 }}
                    @else <span style="color:var(--g400);">—</span>
                    @endif
                </td>
                <td>
                    @if($log->stripe_payment_intent_id)
                    <code style="font-size:10px;font-family:monospace;color:#6366f1;">
                        {{ substr($log->stripe_payment_intent_id, 0, 16) }}...
                    </code>
                    @else
                    <span style="color:var(--g400);font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    @if($log->shopify_order_number)
                    <span style="font-weight:700;color:var(--ok);">{{ $log->shopify_order_number }}</span>
                    @else
                    <span style="color:var(--g400);font-size:12px;">Not synced</span>
                    @endif
                </td>
                <td>
                    @switch($log->status)
                        @case('completed') @case('shopify_order_created')
                            <span class="badge badge-success">✅ Done</span> @break
                        @case('paid')
                            <span class="badge badge-warning">⏳ Sync</span> @break
                        @case('payment_created')
                            <span class="badge badge-info">💳 Checkout</span> @break
                        @case('pending')
                            <span class="badge badge-gray">Pending</span> @break
                        @case('failed')
                            <span class="badge badge-danger">❌ Failed</span> @break
                        @case('expired')
                            <span class="badge badge-gray">Expired</span> @break
                        @case('refunded')
                            <span class="badge badge-purple">↩ Refunded</span> @break
                        @default
                            <span class="badge badge-gray">{{ ucfirst($log->status) }}</span>
                    @endswitch
                </td>
                <td style="font-size:11px;color:var(--g400);white-space:nowrap;">
                    {{ $log->created_at->format('M j, Y') }}<br>
                    {{ $log->created_at->format('g:i a') }}
                </td>
                <td>
                    <a href="{{ route('tenant.logs.detail', $log->session_id) }}" class="btn btn-primary btn-sm">View</a>
                    @if($log->status === 'paid' && !$log->shopify_order_id)
                    <form method="POST" action="{{ route('tenant.sync.order', $log->session_id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;" onclick="this.disabled=true;this.form.submit();">🔄</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:var(--g400);padding:40px;">No transactions found</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())
    <div style="padding:16px;text-align:center;">{{ $logs->withQueryString()->links() }}</div>
    @endif
</div>

@endsection