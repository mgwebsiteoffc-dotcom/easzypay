@extends('admin.layout')
@section('page-title', 'Transaction Logs')
@section('content')

<div class="qp-table-wrap">
    <div class="qp-table-head">
        <div class="qp-table-title">📋 All Transactions ({{ $logs->total() }})</div>
        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}" class="filter-input">
            <select name="status" class="filter-input">
                <option value="">All Status</option>
                <option value="completed" {{ request('status')==='completed'?'selected':'' }}>Completed</option>
                <option value="paid" {{ request('status')==='paid'?'selected':'' }}>Pending Sync</option>
                <option value="payment_created" {{ request('status')==='payment_created'?'selected':'' }}>At Checkout</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Failed</option>
                <option value="expired" {{ request('status')==='expired'?'selected':'' }}>Expired</option>
            </select>
            <select name="tenant" class="filter-input">
                <option value="">All Tenants</option>
                @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ request('tenant')==$t->id?'selected':'' }}>{{ $t->name }} ({{ $t->email }})</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Session</th>
                <th>Tenant</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Stripe PI</th>
                <th>Shopify</th>
                <th>Status</th>
                <th>Date</th> 
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td><code style="font-size:11px;color:#0f766e;">{{ substr($log->session_id, 0, 12) }}...</code></td>
                <td style="font-size:12px;">Tenant #{{ $log->tenant_id ?? '—' }}</td>
                <td>{{ $log->customer_email ?? '—' }}</td>
                <td style="font-weight:700;">{{ $log->formatted_total }}</td>
                <td style="font-size:12px;">
                    @if($log->wallet_type === 'apple_pay') 🍎
                    @elseif($log->wallet_type === 'google_pay') G
                    @else 💳 @endif
                    {{ strtoupper($log->card_brand ?? '') }} {{ $log->card_last4 ?? '' }}
                </td>
                <td><code style="font-size:10px;color:#6366f1;">{{ substr($log->stripe_payment_intent_id ?? '—', 0, 18) }}...</code></td>
                <td><span style="font-weight:700;color:#059669;">{{ $log->shopify_order_number ?? '—' }}</span></td>
                <td>
                    @switch($log->status)
                        @case('completed') @case('shopify_order_created')
                            <span class="badge badge-success">✅</span> @break
                        @case('paid')
                            <span class="badge badge-warning">⏳</span> @break
                        @case('failed')
                            <span class="badge badge-danger">❌</span> @break
                        @default
                            <span class="badge badge-gray">{{ ucfirst($log->status) }}</span>
                    @endswitch
                </td>
                <td style="font-size:11px;color:#9ca3af;">{{ $log->created_at->format('M j, g:ia') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $logs->withQueryString()->links('admin.pagination') }}</div>
</div>

@endsection