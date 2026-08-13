@extends('tenant.layout')
@section('title', 'Orders')
@section('content')

<div class="tw">
    <div class="tw-head">
        <div class="tw-title">📦 Orders</div>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search email, order#..." value="{{ request('search') }}" style="padding:7px 12px;border:1.5px solid var(--g200);border-radius:7px;font-size:13px;outline:none;">
            <select name="status" style="padding:7px 12px;border:1.5px solid var(--g200);border-radius:7px;font-size:13px;">
                <option value="">All Status</option>
                <option value="completed" {{ request('status')==='completed'?'selected':'' }}>✅ Completed</option>
                <option value="paid" {{ request('status')==='paid'?'selected':'' }}>⏳ Pending Sync</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>❌ Failed</option>
                <option value="pending" {{ request('status')==='pending'?'selected':'' }}>Pending</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->anyFilled(['search','status']))
            <a href="{{ route('tenant.orders') }}" class="btn btn-sm" style="background:var(--g100);color:var(--g700);">Clear</a>
            @endif
        </form>
    </div>
    <table>
        <thead><tr><th>Store</th><th>Customer</th><th>Shopify Order</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        @forelse(($orders instanceof \Illuminate\Pagination\LengthAwarePaginator ? $orders : collect($orders)) as $order)
        <tr>
            <td style="font-size:12px;">{{ optional($order->store)->shop_name ?? $order->shop_domain ?? '—' }}</td>
            <td>
                <div>{{ $order->customer_email ?? '—' }}</div>
                @if($order->customer_first_name)
                <div style="font-size:11px;color:var(--g400);">{{ $order->customer_first_name }} {{ $order->customer_last_name }}</div>
                @endif
            </td>
            <td>
                @if($order->shopify_order_number)
                <span style="font-weight:700;color:var(--ok);">{{ $order->shopify_order_number }}</span>
                @else
                <span style="color:var(--g400);font-size:12px;">Not synced</span>
                @endif
            </td>
            <td style="font-weight:700;">{{ $order->formatted_total }}</td>
            <td style="font-size:12px;">
                @if($order->wallet_type === 'apple_pay') 🍎 Apple Pay
                @elseif($order->wallet_type === 'google_pay') G Google Pay
                @elseif($order->wallet_type === 'link') 🔗 Link
                @elseif($order->card_brand) 💳 {{ strtoupper($order->card_brand) }} •••• {{ $order->card_last4 }}
                @else 💳
                @endif
            </td>
            <td>
                @switch($order->status)
                    @case('completed') @case('shopify_order_created')
                        <span class="badge badge-success">✅ Done</span> @break
                    @case('paid')
                        <span class="badge badge-warning">⏳ Sync Pending</span> @break
                    @case('failed')
                        <span class="badge badge-danger">❌ Failed</span> @break
                    @case('payment_created')
                        <span class="badge badge-info">💳 Checkout</span> @break
                    @case('pending')
                        <span class="badge badge-gray">Pending</span> @break
                    @case('expired')
                        <span class="badge badge-gray">Expired</span> @break
                    @default
                        <span class="badge badge-gray">{{ ucfirst($order->status) }}</span>
                @endswitch
            </td>
            <td style="font-size:12px;color:var(--g400);">
                {{ $order->created_at->format('M j, Y') }}<br>
                {{ $order->created_at->format('g:i a') }}
            </td>
            <td>
                @if($order->status === 'paid' && !$order->shopify_order_id)
                <form method="POST" action="{{ route('tenant.sync.order', $order->session_id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" onclick="this.disabled=true;this.textContent='Syncing...';this.form.submit();">
                        🔄 Sync
                    </button>
                </form>
                @elseif($order->shopify_order_number)
                <span style="font-size:11px;color:var(--ok);">✅ Synced</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--g400);padding:40px;">No orders found</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($orders instanceof \Illuminate\Pagination\LengthAwarePaginator && $orders->hasPages())
    <div style="padding:16px;text-align:center;">{{ $orders->withQueryString()->links() }}</div>
    @endif
</div>

@endsection