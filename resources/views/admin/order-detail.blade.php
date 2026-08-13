@extends('admin.layout')

@section('page-title', 'Order Detail')

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('admin.orders') }}" style="color:#667eea;text-decoration:none;font-size:14px;">
        ← Back to Orders
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    <!-- Session Info -->
    <div class="stat-card" style="grid-column:span 2;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div>
                <div class="stat-label">Session ID</div>
                <code style="font-size:13px;color:#667eea;">{{ $session->session_id }}</code>
            </div>
            <div>
                @switch($session->status)
                    @case('completed')
                    @case('shopify_order_created')
                        <span class="badge badge-success" style="font-size:13px;padding:6px 14px;">✅ Completed</span>
                        @break
                    @case('paid')
                        <span class="badge badge-warning" style="font-size:13px;padding:6px 14px;">⏳ Sync Pending</span>
                        @break
                    @case('failed')
                        <span class="badge badge-danger" style="font-size:13px;padding:6px 14px;">❌ Failed</span>
                        @break
                    @default
                        <span class="badge badge-gray" style="font-size:13px;padding:6px 14px;">{{ ucfirst($session->status) }}</span>
                @endswitch
            </div>
        </div>
    </div>

    <!-- Customer -->
    <div class="stat-card">
        <div class="stat-label" style="margin-bottom:14px;">👤 Customer</div>
        <div style="space-y:8px;">
            @foreach([
                'Name'    => trim(($session->customer_first_name ?? '') . ' ' . ($session->customer_last_name ?? '')),
                'Email'   => $session->customer_email,
                'Phone'   => $session->customer_phone,
                'Shop'    => $session->shop_domain,
                'IP'      => $session->ip_address,
                'Country' => $session->country_code,
            ] as $label => $value)
            @if($value)
            <div style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid #f3f4f6;">
                <span style="font-size:12px;color:#9ca3af;width:60px;flex-shrink:0;">{{ $label }}</span>
                <span style="font-size:13px;color:#111827;font-weight:500;">{{ $value }}</span>
            </div>
            @endif
            @endforeach
        </div>
    </div>

    <!-- Shipping -->
    <div class="stat-card">
        <div class="stat-label" style="margin-bottom:14px;">📦 Shipping Address</div>
        @if($session->shipping_address1)
        <div style="font-size:14px;color:#374151;line-height:1.8;">
            {{ $session->customer_first_name }} {{ $session->customer_last_name }}<br>
            {{ $session->shipping_address1 }}<br>
            @if($session->shipping_address2)
                {{ $session->shipping_address2 }}<br>
            @endif
            {{ $session->shipping_city }}, {{ $session->shipping_state }} {{ $session->shipping_zip }}<br>
            {{ $session->shipping_country }}
        </div>
        @else
        <span style="color:#9ca3af;font-size:13px;">No shipping address</span>
        @endif
    </div>

    <!-- Payment -->
    <div class="stat-card">
        <div class="stat-label" style="margin-bottom:14px;">💳 Payment Details</div>
        @foreach([
            'Base Amount'   => $session->formatted_subtotal,
            'Charged'       => $session->formatted_total,
            'Currency'      => $session->charged_currency ?? $session->currency,
            'Exchange Rate' => $session->exchange_rate ? number_format($session->exchange_rate, 6) : '1.0',
            'Stripe PI'     => $session->stripe_payment_intent_id,
            'Shopify Order' => $session->shopify_order_number ?? $session->shopify_order_id,
        ] as $label => $value)
        @if($value)
        <div style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid #f3f4f6;">
            <span style="font-size:12px;color:#9ca3af;min-width:90px;">{{ $label }}</span>
            <span style="font-size:12px;color:#111827;font-weight:500;font-family:monospace;word-break:break-all;">{{ $value }}</span>
        </div>
        @endif
        @endforeach
    </div>

    <!-- Items -->
    <div class="stat-card">
        <div class="stat-label" style="margin-bottom:14px;">🛍️ Items</div>
        @foreach($session->items as $item)
        <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6;">
            @if(!empty($item['image']))
            <img src="{{ $item['image'] }}" style="width:48px;height:48px;border-radius:6px;object-fit:cover;" alt="">
            @endif
            <div style="flex:1;">
                <div style="font-weight:600;font-size:13px;">{{ $item['title'] ?? 'Product' }}</div>
                @if(!empty($item['variant_title']) && $item['variant_title'] !== 'Default Title')
                <div style="font-size:11px;color:#9ca3af;">{{ $item['variant_title'] }}</div>
                @endif
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                    Qty: {{ $item['quantity'] ?? 1 }} ·
                    ${{ number_format(($item['price'] ?? 0) / 100, 2) }} each ·
                    Variant ID: {{ $item['variant_id'] ?? 'N/A' }}
                </div>
            </div>
            <div style="font-weight:700;font-size:14px;">
                ${{ number_format((($item['price'] ?? 0) * ($item['quantity'] ?? 1)) / 100, 2) }}
            </div>
        </div>
        @endforeach
    </div>

</div>

<!-- Payment Logs -->
@if($session->logs->isNotEmpty())
<div class="qp-table-wrap">
    <div class="qp-table-head">
        <div class="qp-table-title">📋 Payment Logs</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Stripe ID</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->logs->sortByDesc('created_at') as $log)
            <tr>
                <td style="font-family:monospace;font-size:12px;">{{ $log->event_type }}</td>
                <td>
                    @if($log->status === 'succeeded' || $log->status === 'success')
                        <span class="badge badge-success">{{ $log->status }}</span>
                    @elseif($log->status === 'failed')
                        <span class="badge badge-danger">{{ $log->status }}</span>
                    @else
                        <span class="badge badge-info">{{ $log->status }}</span>
                    @endif
                </td>
                <td>
                    @if($log->amount)
                        ${{ number_format($log->amount / 100, 2) }}
                        {{ $log->currency }}
                    @else —
                    @endif
                </td>
                <td style="font-size:12px;">
                    @if($log->wallet_type && $log->wallet_type !== 'card')
                        @switch($log->wallet_type)
                            @case('apple_pay')  🍎 Apple Pay  @break
                            @case('google_pay') G Google Pay @break
                            @case('link')       🔗 Link       @break
                            @default            {{ $log->wallet_type }}
                        @endswitch
                    @elseif($log->card_brand)
                        {{ strtoupper($log->card_brand) }} •••• {{ $log->card_last4 }}
                    @else —
                    @endif
                </td>
                <td style="font-family:monospace;font-size:11px;color:#667eea;">
                    {{ $log->stripe_payment_intent_id ?? $log->stripe_charge_id ?? '—' }}
                </td>
                <td style="font-size:12px;color:#9ca3af;">
                    {{ $log->created_at->format('M j, g:i a') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection