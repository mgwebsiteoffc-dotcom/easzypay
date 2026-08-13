@extends('tenant.layout')
@section('title', 'Orders')
@section('content')

<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Orders</div>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input class="fld" type="text" name="search" placeholder="Email or order #" value="{{ request('search') }}">
            <select class="fld" name="status">
                <option value="">All status</option>
                <option value="completed" {{ request('status')==='completed'?'selected':'' }}>Completed</option>
                <option value="paid" {{ request('status')==='paid'?'selected':'' }}>Pending sync</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Failed</option>
                <option value="pending" {{ request('status')==='pending'?'selected':'' }}>Pending</option>
            </select>
            <button type="submit" class="btn btn-sm">Filter</button>
            @if(request()->anyFilled(['search','status']))
            <a href="{{ route('tenant.orders') }}" class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>
    </div>
    <table>
        <thead><tr><th>Store</th><th>Customer</th><th>Shopify</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        @forelse(($orders instanceof \Illuminate\Pagination\LengthAwarePaginator ? $orders : collect($orders)) as $order)
        <tr>
            <td style="font-size:13px;">{{ optional($order->store)->shop_name ?? $order->shop_domain ?? '—' }}</td>
            <td>
                <div>{{ $order->customer_email ?? '—' }}</div>
                @if($order->customer_first_name)
                <div style="font-size:12px;color:var(--muted);">{{ $order->customer_first_name }} {{ $order->customer_last_name }}</div>
                @endif
            </td>
            <td>
                @if($order->shopify_order_number)
                <strong>{{ $order->shopify_order_number }}</strong>
                @else
                <span style="color:var(--muted);font-size:12px;">Not synced</span>
                @endif
            </td>
            <td style="font-weight:700;">{{ $order->formatted_total }}</td>
            <td style="font-size:13px;">
                @if($order->wallet_type === 'apple_pay') Apple Pay
                @elseif($order->wallet_type === 'google_pay') Google Pay
                @elseif($order->wallet_type === 'link') Link
                @elseif($order->card_brand) {{ strtoupper($order->card_brand) }} ···· {{ $order->card_last4 }}
                @else Card
                @endif
            </td>
            <td>
                @switch($order->status)
                    @case('completed') @case('shopify_order_created')
                        <span class="badge badge-success">Done</span> @break
                    @case('paid')
                        <span class="badge badge-warning">Sync pending</span> @break
                    @case('failed')
                        <span class="badge badge-danger">Failed</span> @break
                    @default
                        <span class="badge badge-gray">{{ ucfirst($order->status) }}</span>
                @endswitch
            </td>
            <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                {{ $order->created_at->format('M j, Y') }}<br>{{ $order->created_at->format('g:i a') }}
            </td>
            <td>
                <a href="{{ route('tenant.logs.detail', $order->session_id) }}" class="btn btn-sm btn-ghost">View</a>
                @if($order->status === 'paid' && !$order->shopify_order_id)
                <form method="POST" action="{{ route('tenant.sync.order', $order->session_id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm">Sync</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">No orders yet</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($orders instanceof \Illuminate\Pagination\LengthAwarePaginator && $orders->hasPages())
    <div style="padding:16px;text-align:center;">{{ $orders->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
