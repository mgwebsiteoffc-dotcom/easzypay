@extends('tenant.layout')
@section('title', 'Dashboard')

@section('content')
@php
    $trialDays = 0;
    if ($tenant->trial_ends_at) {
        $trialDays = (int) max(0, ceil(now()->floatDiffInDays($tenant->trial_ends_at, false)));
    }
@endphp

@if($tenant->isTrialExpired())
<div class="alert alert-danger">Trial ended. <a href="{{ route('tenant.settings') }}" style="text-decoration:underline;font-weight:700;">Upgrade your plan</a> to keep taking payments.</div>
@elseif($tenant->isOnTrial())
<div class="alert alert-info">Trial · {{ $trialDays }} {{ $trialDays === 1 ? 'day' : 'days' }} left</div>
@endif

<div class="stats">
    <div class="stat success">
        <div class="stat-label">Total revenue</div>
        <div class="stat-val">${{ number_format($stats['total_revenue'], 2) }}</div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat primary">
        <div class="stat-label">Today</div>
        <div class="stat-val">${{ number_format($stats['today_revenue'], 2) }}</div>
        <div class="stat-sub">{{ now()->format('M j') }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Orders</div>
        <div class="stat-val">{{ number_format($stats['total_orders']) }}</div>
        <div class="stat-sub">Completed</div>
    </div>
    <div class="stat">
        <div class="stat-label">Today’s orders</div>
        <div class="stat-val">{{ $stats['today_orders'] }}</div>
        <div class="stat-sub">{{ now()->format('M j') }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Stores</div>
        <div class="stat-val">{{ $stats['total_stores'] }}</div>
        <div class="stat-sub">Connected</div>
    </div>
    @if($stats['pending_sync'] > 0)
    <div class="stat warning">
        <div class="stat-label">Pending sync</div>
        <div class="stat-val">{{ $stats['pending_sync'] }}</div>
        <div class="stat-sub">Need Shopify sync</div>
    </div>
    @endif
</div>

@if($stores->isEmpty())
<div class="cta-box">
    <h2>Connect your Shopify store</h2>
    <p>Install EaszyPay so checkout can charge in the shopper’s currency.</p>
    <div class="form-inline">
        <input type="text" id="shop-input" placeholder="yourstore.myshopify.com" autocomplete="off">
        <button type="button" onclick="connectShop(document.getElementById('shop-input').value)">Connect</button>
    </div>
</div>
@else

@foreach($stores as $store)
<div style="margin-bottom:28px;">
    <h2 style="font-family:'Iowan Old Style',Palatino,Georgia,serif;font-size:22px;letter-spacing:-0.02em;margin-bottom:14px;">
        Setup · {{ $store->shop_name }}
    </h2>

    <div class="step-card">
        <span class="step-num">1</span>
        <span class="step-title">Store connected</span>
        <div class="step-desc">
            <strong>{{ $store->shop_name }}</strong> · {{ $store->myshopify_domain }}<br>
            Plan {{ $store->shopify_plan ?? 'Basic' }} · Catalog currency {{ $store->currency }}
        </div>
    </div>

    <div class="step-card">
        <span class="step-num">2</span>
        <span class="step-title">Create the theme snippet</span>
        <div class="step-desc">
            <div class="note note-warn">
                Online Store → Themes → Edit code → Snippets → Add a new snippet named
                <code>easzypay-button</code>. Replace the file with the code below and save.
            </div>
            <a href="https://{{ $store->myshopify_domain }}/admin/themes/current/?key=snippets%2Feaszypay-button.liquid" target="_blank" rel="noopener" class="btn btn-sm">Open theme editor</a>
            <div style="display:flex;justify-content:space-between;align-items:center;margin:14px 0 8px;">
                <strong style="font-size:13px;color:var(--ink);">Snippet</strong>
                <button type="button" onclick="copyCode('snippet-{{ $store->id }}', this)" class="btn btn-sm btn-ghost">Copy</button>
            </div>
            <div class="code-block">
                <pre id="snippet-{{ $store->id }}" style="margin:0;white-space:pre-wrap;word-break:break-word;font:inherit;color:inherit;">{{ $store->getSnippetCode() }}</pre>
            </div>
        </div>
    </div>

    <div class="step-card">
        <span class="step-num">3</span>
        <span class="step-title">Add the button on the product page</span>
        <div class="step-desc">
            <div class="note note-info">
                In <code>main-product.liquid</code> (or <code>product-template.liquid</code>), paste this line after the Add to cart form.
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <strong style="font-size:13px;color:var(--ink);">Render line</strong>
                <button type="button" onclick="copyCode('render-{{ $store->id }}', this)" class="btn btn-sm btn-ghost">Copy</button>
            </div>
            <div class="code-block">
                <pre id="render-{{ $store->id }}" style="margin:0;font:inherit;color:inherit;">{!! '{%- render \'easzypay-button\' -%}' !!}</pre>
            </div>
            <div class="note note-ok">Place it after Shopify’s Add to cart button so both options show.</div>
        </div>
    </div>

    <div class="step-card">
        <span class="step-num">4</span>
        <span class="step-title">Test checkout</span>
        <div class="step-desc">
            Open a product page, use Buy Now, and pay with test card <code>4242 4242 4242 4242</code>.
            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                <a href="https://{{ $store->myshopify_domain }}" target="_blank" rel="noopener" class="btn btn-sm">Visit store</a>
                <a href="{{ route('tenant.customize', $store->id) }}" class="btn btn-sm btn-ghost">Customize button</a>
            </div>
        </div>
    </div>

    @if(!$store->button_active)
    <div class="step-card">
        <span class="step-num">5</span>
        <span class="step-title">Mark setup complete</span>
        <div class="step-desc">
            After a successful test payment:
            <form method="POST" action="{{ route('tenant.mark-setup-done', $store->id) }}" style="margin-top:12px;">
                @csrf
                <button type="submit" class="btn btn-sm">Mark complete</button>
            </form>
        </div>
    </div>
    @else
    <div class="step-card" style="border-color:#b7e0d8;background:#f3faf8;">
        <span class="step-num">✓</span>
        <span class="step-title">Setup complete</span>
        <div class="step-desc">Buy Now is live on {{ $store->shop_name }}.</div>
    </div>
    @endif
</div>
@endforeach

<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Stores</div>
        <button type="button" onclick="var e=document.getElementById('add-s');e.style.display=e.style.display==='none'?'block':'none'" class="btn btn-sm">Add store</button>
    </div>
    <div id="add-s" style="display:none;padding:14px 20px;background:#faf8f3;border-bottom:1px solid var(--line);">
        <div class="form-inline" style="margin:0;justify-content:flex-start;max-width:520px;">
            <input type="text" id="ns" placeholder="yourstore.myshopify.com" style="border:1px solid var(--line);">
            <button type="button" class="btn" onclick="connectShop(document.getElementById('ns').value)">Connect</button>
        </div>
    </div>
    <table>
        <thead><tr><th>Store</th><th>Status</th><th>Button</th><th>Orders</th><th>Revenue</th><th></th></tr></thead>
        <tbody>
        @foreach($stores as $store)
        <tr>
            <td>
                <div style="font-weight:700;">{{ $store->shop_name }}</div>
                <div style="font-size:12px;color:var(--gray-400);">{{ $store->myshopify_domain }}</div>
            </td>
            <td>@if($store->is_installed)<span class="badge badge-success">Active</span>@else<span class="badge badge-danger">Off</span>@endif</td>
            <td>@if($store->button_active)<span class="badge badge-success">Live</span>@else<span class="badge badge-warning">Setup</span>@endif</td>
            <td>{{ number_format($store->total_orders) }}</td>
            <td>${{ number_format($store->total_revenue, 2) }}</td>
            <td><a href="{{ route('tenant.customize', $store->id) }}" class="btn btn-sm btn-ghost">Customize</a></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<?php
$pendingOrders = \App\Models\CheckoutSession::where('tenant_id', $tenant->id)
    ->where('status', 'paid')
    ->whereNull('shopify_order_id')
    ->orderByDesc('created_at')
    ->limit(20)
    ->get();
?>
@if($pendingOrders->isNotEmpty())
<div class="tw">
    <div class="tw-head"><div class="tw-title">Pending Shopify sync ({{ $pendingOrders->count() }})</div></div>
    <table>
        <thead><tr><th>Session</th><th>Customer</th><th>Amount</th><th>Stripe</th><th>Paid</th><th></th></tr></thead>
        <tbody>
        @foreach($pendingOrders as $po)
        <tr>
            <td style="font-family:ui-monospace,monospace;font-size:12px;">{{ substr($po->session_id, 0, 16) }}…</td>
            <td>{{ $po->customer_email ?? '—' }}</td>
            <td style="font-weight:700;">{{ $po->formatted_total }}</td>
            <td style="font-family:ui-monospace,monospace;font-size:12px;">{{ substr($po->stripe_payment_intent_id ?? '—', 0, 18) }}</td>
            <td style="font-size:12px;color:var(--gray-400);">{{ $po->paid_at ? $po->paid_at->format('M j, g:ia') : $po->created_at->format('M j, g:ia') }}</td>
            <td>
                <form method="POST" action="{{ route('tenant.sync.order', $po->session_id) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm" onclick="this.disabled=true;this.textContent='Syncing…';this.form.submit();">Sync</button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if($recentOrders->isNotEmpty())
<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Recent orders</div>
        <a href="{{ route('tenant.orders') }}" class="btn btn-sm btn-ghost">View all</a>
    </div>
    <table>
        <thead><tr><th>Customer</th><th>Shopify</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        @foreach($recentOrders as $order)
        <tr>
            <td>{{ $order->customer_email ?? '—' }}</td>
            <td>@if($order->shopify_order_number)<strong>{{ $order->shopify_order_number }}</strong>@else<span style="color:var(--gray-400);">—</span>@endif</td>
            <td style="font-weight:700;">{{ $order->formatted_total }}</td>
            <td style="font-size:12px;">
                @if($order->wallet_type === 'apple_pay') Apple Pay
                @elseif($order->wallet_type === 'google_pay') Google Pay
                @else Card @endif
                {{ strtoupper($order->card_brand ?? '') }} {{ $order->card_last4 ?? '' }}
            </td>
            <td>
                @if(in_array($order->status, ['completed','shopify_order_created']))
                    <span class="badge badge-success">Paid</span>
                @elseif($order->status === 'paid')
                    <span class="badge badge-warning">Syncing</span>
                @else
                    <span class="badge badge-gray">{{ ucfirst($order->status) }}</span>
                @endif
            </td>
            <td style="font-size:12px;color:var(--gray-400);">{{ $order->created_at->format('M j, g:ia') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<script>
function connectShop(raw) {
    var s = (raw || '').trim();
    if (!s) return;
    if (s.indexOf('.myshopify.com') === -1) s += '.myshopify.com';
    window.location.href = '/shopify/install?shop=' + encodeURIComponent(s);
}
function copyCode(elementId, button) {
    var el = document.getElementById(elementId);
    var text = el.textContent || el.innerText;
    var done = function () {
        var original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(function () { button.textContent = original; }, 1600);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(done).catch(function () { fallbackCopy(text, done); });
    } else {
        fallbackCopy(text, done);
    }
}
function fallbackCopy(text, done) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch (e) {}
    document.body.removeChild(ta);
}
</script>
@endsection
