@extends('admin.layout')

@section('page-title', 'Dashboard')

@section('content')

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card success">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">${{ number_format($stats['total_revenue'], 2) }}</div>
        <div class="stat-sub">All time</div>
    </div>

    <div class="stat-card primary">
        <div class="stat-label">Today's Revenue</div>
        <div class="stat-value">${{ number_format($stats['today_revenue'], 2) }}</div>
        <div class="stat-sub">{{ now()->format('M j, Y') }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value">{{ number_format($stats['total_orders']) }}</div>
        <div class="stat-sub">Completed</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Today's Orders</div>
        <div class="stat-value">{{ $stats['today_orders'] }}</div>
        <div class="stat-sub">{{ now()->format('M j') }}</div>
    </div>

    <div class="stat-card warning">
        <div class="stat-label">Pending Sessions</div>
        <div class="stat-value">{{ $stats['pending_sessions'] }}</div>
        <div class="stat-sub">Active checkouts</div>
    </div>

    <div class="stat-card danger">
        <div class="stat-label">Failed Payments</div>
        <div class="stat-value">{{ $stats['failed_payments'] }}</div>
        <div class="stat-sub">Today</div>
    </div>

    @if($stats['pending_shopify'] > 0)
    <div class="stat-card warning">
        <div class="stat-label">Shopify Sync Pending</div>
        <div class="stat-value">{{ $stats['pending_shopify'] }}</div>
        <div class="stat-sub">Need Shopify order</div>
    </div>
    @endif

    @if($stats['failed_webhooks'] > 0)
    <div class="stat-card danger">
        <div class="stat-label">Failed Webhooks</div>
        <div class="stat-value">{{ $stats['failed_webhooks'] }}</div>
        <div class="stat-sub">
            <a href="{{ route('admin.webhooks', ['status' => 'failed']) }}" style="color:inherit;">View →</a>
        </div>
    </div>
    @endif
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">

    <!-- Revenue Chart -->
    <div class="chart-wrap">
        <div class="chart-title">📈 Revenue — Last 7 Days</div>
        @php
            $maxRevenue = $revenueChart->max('revenue') ?: 1;
        @endphp
        <div class="chart-bars">
            @foreach($revenueChart as $day)
            <div class="chart-bar-wrap">
                <div style="font-size:10px;color:#6b7280;font-weight:600;margin-bottom:4px;">
                    ${{ number_format($day->revenue, 0) }}
                </div>
                <div class="chart-bar-inner">
                    <div
                        class="chart-bar"
                        style="height: {{ max(4, ($day->revenue / $maxRevenue) * 100) }}%;"
                        title="{{ $day->orders }} orders · ${{ number_format($day->revenue, 2) }}"
                    ></div>
                </div>
                <div class="chart-bar-label">
                    {{ \Carbon\Carbon::parse($day->date)->format('M j') }}
                </div>
            </div>
            @endforeach

            @if($revenueChart->isEmpty())
            <div style="color:#9ca3af;font-size:13px;padding:20px;text-align:center;width:100%;">
                No data for last 7 days
            </div>
            @endif
        </div>
    </div>

    <!-- Currency Breakdown -->
    <div class="chart-wrap">
        <div class="chart-title">💱 Currency Breakdown</div>
        @forelse($currencyBreakdown as $item)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f3f4f6;">
            <div>
                <div style="font-weight:700;font-size:14px;">{{ $item->charged_currency }}</div>
                <div style="font-size:11px;color:#9ca3af;">{{ $item->count }} orders</div>
            </div>
            <div style="font-weight:700;color:#0f766e;">
                ${{ number_format($item->total, 2) }}
            </div>
        </div>
        @empty
        <div style="color:#9ca3af;font-size:13px;padding:12px 0;">No data yet</div>
        @endforelse
    </div>

</div>

<!-- Payment Methods -->
@if($paymentMethods->isNotEmpty())
<div class="chart-wrap" style="margin-bottom:24px;">
    <div class="chart-title">💳 Payment Methods</div>
    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        @foreach($paymentMethods as $method)
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px 20px;text-align:center;">
            <div style="font-size:20px;margin-bottom:4px;">
                @switch($method->wallet_type)
                    @case('apple_pay')  🍎 @break
                    @case('google_pay') 📱 @break
                    @case('link')       🔗 @break
                    @default            💳
                @endswitch
            </div>
            <div style="font-weight:700;font-size:14px;">
                {{ $method->wallet_type ? ucfirst(str_replace('_', ' ', $method->wallet_type)) : 'Card' }}
            </div>
            <div style="color:#9ca3af;font-size:12px;">{{ $method->count }} payments</div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Recent Orders -->
<div class="qp-table-wrap">
    <div class="qp-table-head">
        <div class="qp-table-title">📦 Recent Orders</div>
        <a href="{{ route('admin.orders') }}" class="btn btn-primary btn-sm">View All →</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Session</th>
                <th>Customer</th>
                <th>Shopify Order</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentOrders as $order)
            <tr>
                <td>
                    <a href="{{ route('admin.orders.detail', $order->session_id) }}"
                       style="color:#0f766e;font-family:monospace;font-size:12px;text-decoration:none;">
                        {{ substr($order->session_id, 0, 12) }}...
                    </a>
                </td>
                <td>{{ $order->customer_email ?? '—' }}</td>
                <td>
                    @if($order->shopify_order_number)
                        <span style="font-weight:600;">{{ $order->shopify_order_number }}</span>
                    @else
                        <span style="color:#9ca3af;">Pending</span>
                    @endif
                </td>
                <td style="font-weight:700;">
                    @if($order->charged_amount)
                        {{ $order->formatted_total }}
                    @else
                        {{ $order->formatted_subtotal }}
                    @endif
                </td>
                <td>{{ $order->charged_currency ?? $order->currency }}</td>
                <td>
                    @switch($order->status)
                        @case('completed')
                        @case('shopify_order_created')
                            <span class="badge badge-success">✅ Completed</span>
                            @break
                        @case('paid')
                            <span class="badge badge-warning">⏳ Sync Pending</span>
                            @break
                        @case('failed')
                            <span class="badge badge-danger">❌ Failed</span>
                            @break
                        @default
                            <span class="badge badge-gray">{{ ucfirst($order->status) }}</span>
                    @endswitch
                </td>
                <td style="color:#6b7280;font-size:13px;">
                    {{ $order->created_at->format('M j, g:ia') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;color:#9ca3af;padding:32px;">
                    No orders yet
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection