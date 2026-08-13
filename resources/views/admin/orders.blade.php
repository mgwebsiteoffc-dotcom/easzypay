@extends('admin.layout')

@section('page-title', 'Orders')

@section('content')

<div class="qp-table-wrap">
    <div class="qp-table-head">
        <div class="qp-table-title">📦 All Orders</div>

        <form method="GET" action="{{ route('admin.orders') }}" class="filters">
            <input
                type="text"
                name="search"
                class="filter-input"
                placeholder="Search email, order#, session..."
                value="{{ request('search') }}"
            >
            <select name="status" class="filter-input">
                <option value="">All Statuses</option>
                <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Completed</option>
                <option value="paid"        {{ request('status') === 'paid'        ? 'selected' : '' }}>Paid (Sync Pending)</option>
                <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>Pending</option>
                <option value="failed"      {{ request('status') === 'failed'      ? 'selected' : '' }}>Failed</option>
                <option value="refunded"    {{ request('status') === 'refunded'    ? 'selected' : '' }}>Refunded</option>
            </select>
            <select name="currency" class="filter-input">
                <option value="">All Currencies</option>
                @foreach(['USD','EUR','GBP','AUD','CAD','SGD','AED','INR','JPY'] as $cur)
                <option value="{{ $cur }}" {{ request('currency') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                @endforeach
            </select>
            <input
                type="date"
                name="date"
                class="filter-input"
                value="{{ request('date') }}"
            >
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="{{ route('admin.orders') }}" class="btn" style="background:#f3f4f6;color:#374151;">Clear</a>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Session ID</th>
                <th>Customer</th>
                <th>Shop</th>
                <th>Shopify Order</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>
                    <code style="font-size:11px;color:#0f766e;">
                        {{ substr($order->session_id, 0, 16) }}...
                    </code>
                </td>
                <td>
                    <div style="font-weight:600;font-size:13px;">
                        {{ $order->customer_email ?? '—' }}
                    </div>
                    @if($order->customer_first_name)
                    <div style="font-size:12px;color:#9ca3af;">
                        {{ $order->customer_first_name }} {{ $order->customer_last_name }}
                    </div>
                    @endif
                </td>
                <td style="font-size:12px;color:#6b7280;">
                    {{ $order->shop_domain }}
                </td>
                <td>
                    @if($order->shopify_order_number)
                    <span style="font-weight:700;color:#059669;">{{ $order->shopify_order_number }}</span>
                    @else
                    <span style="color:#9ca3af;font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    <div style="font-weight:700;">
                        @if($order->charged_amount)
                        {{ $order->formatted_total }}
                        @else
                        {{ $order->formatted_subtotal }}
                        @endif
                    </div>
                    @if($order->charged_currency && $order->charged_currency !== $order->currency)
                    <div style="font-size:11px;color:#9ca3af;">
                        Base: {{ $order->currency }}
                        · Rate: {{ number_format($order->exchange_rate, 4) }}
                    </div>
                    @endif
                </td>
                <td>
                    @php
                        $log = $order->logs->where('event_type', 'payment_intent.succeeded')->first();
                    @endphp
                    @if($log)
                    <div style="font-size:12px;">
                        @if($log->wallet_type && $log->wallet_type !== 'card')
                            @switch($log->wallet_type)
                                @case('apple_pay')  🍎 Apple Pay  @break
                                @case('google_pay') G Google Pay @break
                                @case('link')       🔗 Link       @break
                                @default            {{ $log->wallet_type }}
                            @endswitch
                        @else
                        {{ strtoupper($log->card_brand ?? '') }} •••• {{ $log->card_last4 ?? '' }}
                        @endif
                    </div>
                    @else
                    <span style="color:#9ca3af;font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    @switch($order->status)
                        @case('completed')
                        @case('shopify_order_created')
                            <span class="badge badge-success">✅ Done</span>
                            @break
                        @case('paid')
                            <span class="badge badge-warning">⏳ Sync Pending</span>
                            @break
                        @case('payment_created')
                            <span class="badge badge-info">💳 Checkout</span>
                            @break
                        @case('pending')
                            <span class="badge badge-gray">Pending</span>
                            @break
                        @case('failed')
                            <span class="badge badge-danger">❌ Failed</span>
                            @break
                        @case('refunded')
                            <span class="badge badge-purple">↩ Refunded</span>
                            @break
                        @case('expired')
                            <span class="badge badge-gray">Expired</span>
                            @break
                        @default
                            <span class="badge badge-gray">{{ ucfirst($order->status) }}</span>
                    @endswitch
                </td>
                <td style="font-size:12px;color:#6b7280;white-space:nowrap;">
                    {{ $order->created_at->format('M j, Y') }}<br>
                    {{ $order->created_at->format('g:i a') }}
                </td>
                <td>
                    <a
                        href="{{ route('admin.orders.detail', $order->session_id) }}"
                        class="btn btn-primary btn-sm"
                    >View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center;color:#9ca3af;padding:40px;">
                    No orders found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        {{ $orders->withQueryString()->links('admin.pagination') }}
    </div>
</div>

@endsection