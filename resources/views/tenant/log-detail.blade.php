@extends('tenant.layout')
@section('title', 'Transaction Detail')
@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('tenant.logs') }}" style="color:var(--p);text-decoration:none;font-size:14px;">← Back to Logs</a>
</div>

<!-- Status Banner -->
<div style="background:#fff;border:1px solid var(--g200);border-radius:12px;padding:24px;margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;">Transaction Details</h2>
            <code style="font-size:12px;color:var(--g500);">{{ $session->session_id }}</code>
        </div>
        <div>
            @switch($session->status)
                @case('completed') @case('shopify_order_created')
                    <span class="badge badge-success" style="font-size:14px;padding:8px 16px;">✅ Completed</span> @break
                @case('paid')
                    <span class="badge badge-warning" style="font-size:14px;padding:8px 16px;">⏳ Pending Sync</span> @break
                @case('failed')
                    <span class="badge badge-danger" style="font-size:14px;padding:8px 16px;">❌ Failed</span> @break
                @default
                    <span class="badge badge-gray" style="font-size:14px;padding:8px 16px;">{{ ucfirst($session->status) }}</span>
            @endswitch
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    <!-- Customer -->
    <div class="stat">
        <div class="stat-label" style="margin-bottom:14px;">👤 Customer</div>
        @foreach([
            'Name' => trim(($session->customer_first_name ?? '') . ' ' . ($session->customer_last_name ?? '')),
            'Email' => $session->customer_email,
            'Phone' => $session->customer_phone,
            'IP' => $session->ip_address,
            'Country' => $session->country_code,
        ] as $label => $value)
        @if($value)
        <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <span style="color:var(--g400);min-width:60px;">{{ $label }}</span>
            <span style="font-weight:600;">{{ $value }}</span>
        </div>
        @endif
        @endforeach
    </div>

    <!-- Shipping -->
    <div class="stat">
        <div class="stat-label" style="margin-bottom:14px;">📦 Shipping Address</div>
        @if($session->shipping_address1)
        <div style="font-size:14px;line-height:1.7;color:var(--g700);">
            {{ $session->customer_first_name }} {{ $session->customer_last_name }}<br>
            {{ $session->shipping_address1 }}<br>
            @if($session->shipping_address2){{ $session->shipping_address2 }}<br>@endif
            {{ $session->shipping_city }}, {{ $session->shipping_state }} {{ $session->shipping_zip }}<br>
            {{ $session->shipping_country }}
        </div>
        @else
        <span style="color:var(--g400);font-size:13px;">No shipping address</span>
        @endif
    </div>

    <!-- Payment Details -->
    <div class="stat">
        <div class="stat-label" style="margin-bottom:14px;">💳 Payment Details</div>
        <div style="font-size:13px;">
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Amount Paid</span>
                <span style="font-weight:700;color:var(--p);">{{ $session->formatted_total }}</span>
            </div>
            @if($session->charged_currency && $session->charged_currency !== $session->currency)
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Base Currency</span>
                <span style="font-weight:600;">{{ $session->currency }} ({{ $session->formatted_subtotal }})</span>
            </div>
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Exchange Rate</span>
                <span style="font-weight:600;">{{ number_format($session->exchange_rate, 6) }}</span>
            </div>
            @endif
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Method</span>
                <span style="font-weight:600;">
                    @if($session->wallet_type === 'apple_pay') 🍎 Apple Pay
                    @elseif($session->wallet_type === 'google_pay') G Google Pay
                    @elseif($session->wallet_type === 'link') 🔗 Link
                    @else 💳 Card @endif
                </span>
            </div>
            @if($session->card_brand)
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Card</span>
                <span style="font-weight:600;">{{ strtoupper($session->card_brand) }} •••• {{ $session->card_last4 }}</span>
            </div>
            @endif
            @if($session->card_country)
            <div style="display:flex;gap:8px;padding:8px 0;">
                <span style="color:var(--g400);min-width:120px;">Card Country</span>
                <span style="font-weight:600;">{{ $session->card_country }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Order Info -->
    <div class="stat">
        <div class="stat-label" style="margin-bottom:14px;">🏪 Order Info</div>
        <div style="font-size:13px;">
            @if($session->shopify_order_number)
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Shopify Order</span>
                <span style="font-weight:700;color:var(--ok);">{{ $session->shopify_order_number }}</span>
            </div>
            @endif
            @if($session->shop_domain)
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Store</span>
                <span style="font-weight:600;">{{ $session->shop_domain }}</span>
            </div>
            @endif
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Cart Token</span>
                <code style="font-size:11px;">{{ $session->cart_token ?? '—' }}</code>
            </div>
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:120px;">Created</span>
                <span style="font-weight:600;">{{ $session->created_at->format('M j, Y g:i a') }}</span>
            </div>
            @if($session->paid_at)
            <div style="display:flex;gap:8px;padding:8px 0;">
                <span style="color:var(--g400);min-width:120px;">Paid At</span>
                <span style="font-weight:600;color:var(--ok);">{{ $session->paid_at->format('M j, Y g:i a') }}</span>
            </div>
            @endif
            @if($session->shopify_thank_you_url)
            <div style="margin-top:12px;">
                <a href="{{ $session->shopify_thank_you_url }}" target="_blank" class="btn btn-primary btn-sm">
                    🎉 View Shopify Order Page →
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Stripe Details -->
    @if($session->stripe_payment_intent_id)
    <div class="stat" style="grid-column:span 2;">
        <div class="stat-label" style="margin-bottom:14px;">💎 Stripe Details</div>
        <div style="font-size:13px;">
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:160px;">Payment Intent ID</span>
                <code style="font-size:12px;">{{ $session->stripe_payment_intent_id }}</code>
            </div>
            @if($session->stripe_charge_id)
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:160px;">Charge ID</span>
                <code style="font-size:12px;">{{ $session->stripe_charge_id }}</code>
            </div>
            @endif
            @if($stripeData)
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <span style="color:var(--g400);min-width:160px;">Stripe Status</span>
                <span style="font-weight:600;color:var(--ok);">{{ $stripeData->status }}</span>
            </div>
            <div style="display:flex;gap:8px;padding:8px 0;">
                <span style="color:var(--g400);min-width:160px;">Created in Stripe</span>
                <span style="font-weight:600;">{{ \Carbon\Carbon::createFromTimestamp($stripeData->created)->format('M j, Y g:i a') }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Items -->
    @if(!empty($session->items))
    <div class="stat" style="grid-column:span 2;">
        <div class="stat-label" style="margin-bottom:14px;">🛍️ Items</div>
        @foreach($session->items as $item)
        <div style="display:flex;gap:14px;padding:12px 0;border-bottom:1px solid #f3f4f6;">
            @if(!empty($item['image']))
            <img src="{{ $item['image'] }}" style="width:50px;height:50px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;">
            @endif
            <div style="flex:1;">
                <div style="font-weight:600;font-size:14px;">{{ $item['title'] ?? 'Product' }}</div>
                @if(!empty($item['variant_title']) && $item['variant_title'] !== 'Default Title')
                <div style="font-size:12px;color:var(--g500);">{{ $item['variant_title'] }}</div>
                @endif
                <div style="font-size:11px;color:var(--g400);margin-top:2px;">
                    Variant ID: {{ $item['variant_id'] ?? '—' }} ·
                    Product ID: {{ $item['product_id'] ?? '—' }}
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;">${{ number_format(($item['price'] ?? 0) / 100, 2) }}</div>
                <div style="font-size:12px;color:var(--g400);">x {{ $item['quantity'] ?? 1 }}</div>
                <div style="font-weight:700;color:var(--p);margin-top:4px;">
                    ${{ number_format((($item['price'] ?? 0) * ($item['quantity'] ?? 1)) / 100, 2) }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<!-- Activity Logs -->
@if($paymentLogs->isNotEmpty())
<div class="tw">
    <div class="tw-head">
        <div class="tw-title">📋 Activity Timeline ({{ $paymentLogs->count() }})</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Error</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentLogs as $log)
            <tr>
                <td style="font-family:monospace;font-size:12px;">{{ $log->event_type }}</td>
                <td>
                    @if(in_array($log->status, ['success','succeeded']))
                        <span class="badge badge-success">{{ $log->status }}</span>
                    @elseif($log->status === 'failed')
                        <span class="badge badge-danger">{{ $log->status }}</span>
                    @else
                        <span class="badge badge-info">{{ $log->status }}</span>
                    @endif
                </td>
                <td>
                    @if($log->amount)
                    ${{ number_format($log->amount/100, 2) }} {{ $log->currency }}
                    @else — @endif
                </td>
                <td style="font-size:12px;">
                    @if($log->wallet_type && $log->wallet_type !== 'card')
                        @switch($log->wallet_type)
                            @case('apple_pay') 🍎 Apple Pay @break
                            @case('google_pay') G Google Pay @break
                            @case('link') 🔗 Link @break
                            @default {{ $log->wallet_type }}
                        @endswitch
                    @elseif($log->card_brand)
                        {{ strtoupper($log->card_brand) }} •••• {{ $log->card_last4 }}
                    @else — @endif
                </td>
                <td style="font-family:monospace;font-size:10px;color:#6366f1;">
                    @if($log->shopify_order_id) S: {{ $log->shopify_order_id }} @endif
                    @if($log->stripe_charge_id) <br>C: {{ substr($log->stripe_charge_id, 0, 20) }}...@endif
                </td>
                <td style="font-size:11px;color:var(--er);max-width:200px;word-break:break-word;">
                    {{ $log->error_message ?? '—' }}
                </td>
                <td style="font-size:11px;color:var(--g400);white-space:nowrap;">
                    {{ $log->created_at->format('M j, g:i a') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Sync Button -->
@if($session->status === 'paid' && !$session->shopify_order_id)
<div style="margin-top:20px;padding:20px;background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;text-align:center;">
    <p style="font-size:14px;color:#92400e;margin-bottom:12px;">⚠️ This order has been paid but not synced to Shopify yet.</p>
    <form method="POST" action="{{ route('tenant.sync.order', $session->session_id) }}">
        @csrf
        <button type="submit" class="btn btn-primary" onclick="this.disabled=true;this.textContent='Syncing...';this.form.submit();">
            🔄 Sync to Shopify Now
        </button>
    </form>
</div>
@endif

@endsection