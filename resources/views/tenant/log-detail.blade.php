@extends('tenant.layout')
@section('title', 'Transaction')
@section('content')
@php
    $hidden = ['stripe_client_secret', 'password', 'remember_token'];
    $sessionJson = collect($session->toArray())->except($hidden)->all();
    $eventsJson = $paymentLogs->map(function ($log) use ($hidden) {
        return collect($log->toArray())->except($hidden)->all();
    })->values()->all();
    $payload = [
        'session' => $sessionJson,
        'events'  => $eventsJson,
        'stripe'  => $stripeData ? json_decode(json_encode($stripeData), true) : null,
    ];
    $pretty = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<div style="margin-bottom:16px;">
    <a href="{{ route('tenant.logs') }}" class="btn btn-sm btn-ghost">Back to logs</a>
</div>

<div class="tw" style="margin-bottom:16px;">
    <div class="tw-head">
        <div>
            <div class="tw-title">{{ $session->customer_email ?? 'Checkout session' }}</div>
            <code style="font-size:12px;color:var(--muted);">{{ $session->session_id }}</code>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @switch($session->status)
                @case('completed') @case('shopify_order_created')
                    <span class="badge badge-success">Completed</span> @break
                @case('paid')
                    <span class="badge badge-warning">Pending sync</span> @break
                @case('failed')
                    <span class="badge badge-danger">Failed</span> @break
                @default
                    <span class="badge badge-gray">{{ ucfirst($session->status) }}</span>
            @endswitch
            <button type="button" class="btn btn-sm" onclick="copyJson(this)">Copy JSON</button>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0;border-bottom:1px solid var(--line);">
        <div style="padding:16px 20px;border-right:1px solid var(--line);">
            <div class="stat-label">Amount</div>
            <div style="font-size:20px;font-weight:750;margin-top:6px;">{{ $session->formatted_total }}</div>
            @if($session->charged_currency && $session->charged_currency !== $session->currency)
            <div style="font-size:12px;color:var(--muted);margin-top:4px;">{{ $session->currency }} · rate {{ number_format((float)$session->exchange_rate, 4) }}</div>
            @endif
        </div>
        <div style="padding:16px 20px;border-right:1px solid var(--line);">
            <div class="stat-label">Customer</div>
            <div style="margin-top:6px;font-weight:650;">{{ trim(($session->customer_first_name ?? '').' '.($session->customer_last_name ?? '')) ?: '—' }}</div>
            <div style="font-size:13px;color:var(--muted);">{{ $session->customer_email }}</div>
            @if($session->customer_phone)<div style="font-size:13px;color:var(--muted);">{{ $session->customer_phone }}</div>@endif
        </div>
        <div style="padding:16px 20px;border-right:1px solid var(--line);">
            <div class="stat-label">Payment</div>
            <div style="margin-top:6px;">
                @if($session->wallet_type === 'apple_pay') Apple Pay
                @elseif($session->wallet_type === 'google_pay') Google Pay
                @elseif($session->wallet_type === 'link') Link
                @else Card @endif
                @if($session->card_brand) · {{ strtoupper($session->card_brand) }} ···· {{ $session->card_last4 }} @endif
            </div>
            @if($session->stripe_payment_intent_id)
            <code style="font-size:11px;word-break:break-all;">{{ $session->stripe_payment_intent_id }}</code>
            @endif
        </div>
        <div style="padding:16px 20px;">
            <div class="stat-label">Shopify</div>
            <div style="margin-top:6px;font-weight:650;">{{ $session->shopify_order_number ?: 'Not synced' }}</div>
            <div style="font-size:12px;color:var(--muted);">{{ $session->shop_domain }}</div>
        </div>
    </div>
</div>

@if($session->status === 'paid' && !$session->shopify_order_id)
<div class="alert alert-warning" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <span>Paid, not yet written to Shopify.</span>
    <form method="POST" action="{{ route('tenant.sync.order', $session->session_id) }}">
        @csrf
        <button type="submit" class="btn btn-sm">Sync now</button>
    </form>
</div>
@endif

<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Complete log (JSON)</div>
        <span style="font-size:12px;color:var(--muted);">{{ $paymentLogs->count() }} event{{ $paymentLogs->count() === 1 ? '' : 's' }}</span>
    </div>
    <pre id="log-json" style="margin:0;padding:20px;background:#0b1220;color:#d1fae5;font:12px/1.65 ui-monospace,SFMono-Regular,Menlo,monospace;overflow:auto;max-height:70vh;white-space:pre;">{!! e($pretty) !!}</pre>
</div>

<script>
function copyJson(btn) {
    var text = document.getElementById('log-json').textContent;
    var done = function () { var t = btn.textContent; btn.textContent = 'Copied'; setTimeout(function(){ btn.textContent = t; }, 1600); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done);
    else {
        var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); done(); } catch(e) {}
        document.body.removeChild(ta);
    }
}
</script>
@endsection
