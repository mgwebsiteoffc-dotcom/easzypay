@extends('tenant.layout')
@section('title', 'Dashboard')

@section('content')

@if($tenant->isTrialExpired())
<div class="alert alert-danger">⚠️ Trial expired. <a href="{{ route('tenant.settings') }}" style="color:inherit;text-decoration:underline;font-weight:700;">Upgrade plan</a></div>
@elseif($tenant->isOnTrial())
<div class="alert alert-info">⏳ Trial: <strong>{{ $tenant->trial_ends_at->diffInDays(now()) }} days left</strong></div>
@endif

<!-- Stats -->
<div class="stats">
    <div class="stat success"><div class="stat-label">Total Revenue</div><div class="stat-val">${{ number_format($stats['total_revenue'], 2) }}</div><div class="stat-sub">All time</div></div>
    <div class="stat primary"><div class="stat-label">Today Revenue</div><div class="stat-val">${{ number_format($stats['today_revenue'], 2) }}</div><div class="stat-sub">{{ now()->format('M j') }}</div></div>
    <div class="stat"><div class="stat-label">Total Orders</div><div class="stat-val">{{ number_format($stats['total_orders']) }}</div><div class="stat-sub">Completed</div></div>
    <div class="stat"><div class="stat-label">Today Orders</div><div class="stat-val">{{ $stats['today_orders'] }}</div><div class="stat-sub">{{ now()->format('M j') }}</div></div>
    <div class="stat"><div class="stat-label">Stores</div><div class="stat-val">{{ $stats['total_stores'] }}</div><div class="stat-sub">Connected</div></div>
    @if($stats['pending_sync'] > 0)
    <div class="stat warning"><div class="stat-label">Pending Sync</div><div class="stat-val">{{ $stats['pending_sync'] }}</div><div class="stat-sub">Need Shopify sync</div></div>
    @endif
</div>

<!-- No stores -->
@if($stores->isEmpty())
<div class="cta-box">
    <div style="font-size:48px;margin-bottom:16px;">🏪</div>
    <h2>Connect Your Shopify Store</h2>
    <p>Install EaszyPay to start accepting Stripe payments.</p>
    <div class="form-inline">
        <input type="text" id="shop-input" placeholder="yourstore.myshopify.com">
        <button onclick="var s=document.getElementById('shop-input').value.trim();if(!s)return;if(s.indexOf('.myshopify.com')===-1)s+='.myshopify.com';window.location.href='/shopify/install?shop='+encodeURIComponent(s);">Connect →</button>
    </div>
</div>
@else

<!-- Setup Guide for Each Store -->
@foreach($stores as $store)

<div style="margin-bottom:28px;">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        📋 Setup Guide: <span style="color:var(--p);">{{ $store->shop_name }}</span>
    </h2>

    <!-- Step 1: Store Connected -->
    <div class="step-card">
        <span class="step-num">1</span>
        <span class="step-title">✅ Store Connected</span>
        <div class="step-desc">
            <strong>{{ $store->shop_name }}</strong> ({{ $store->myshopify_domain }}) is connected and authorized.
            <br>Plan: {{ $store->shopify_plan ?? 'Basic' }} · Currency: {{ $store->currency }}
        </div>
    </div>

    <!-- Step 2: Create Snippet File -->
    <div class="step-card">
        <span class="step-num">2</span>
        <span class="step-title">Create Snippet File in Shopify</span>
        <div class="step-desc">
            <p style="margin-bottom:12px;">Go to your Shopify admin and create a snippet file:</p>

            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:14px;margin-bottom:14px;">
                <p style="font-size:14px;color:#92400e;font-weight:600;margin-bottom:8px;">📍 Step-by-step:</p>
                <ol style="font-size:13px;color:#78350f;padding-left:20px;line-height:1.8;">
                    <li>Open <strong>Shopify Admin</strong></li>
                    <li>Click <strong>Online Store → Themes</strong></li>
                    <li>On your active theme, click <strong>"⋯" → Edit code</strong></li>
                    <li>In the left sidebar, find the <strong>Snippets</strong> folder</li>
                    <li>Click <strong>"Add a new snippet"</strong></li>
                    <li>Name it: <code style="background:#fff;padding:2px 8px;border-radius:4px;color:#dc2626;font-weight:700;">easzypay-button</code></li>
                    <li>Click <strong>"Done"</strong></li>
                    <li>Replace ALL content with the code below ↓</li>
                    <li>Click <strong>"Save"</strong></li>
                </ol>
            </div>

            <a href="https://{{ $store->myshopify_domain }}/admin/themes/current/?key=snippets%2Feaszypay-button.liquid" target="_blank" class="btn btn-primary btn-sm" style="margin-bottom:12px;">
                🔗 Open Theme Editor Directly
            </a>

            <div style="margin-top:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong style="font-size:13px;color:var(--g700);">📄 Snippet Code:</strong>
                    <button type="button" onclick="copyCode('snippet-{{ $store->id }}', this)" class="btn btn-primary btn-sm">📋 Copy Code</button>
                </div>
                <div style="background:#1e1b4b;border-radius:8px;padding:16px;max-height:300px;overflow-y:auto;">
                    <pre id="snippet-{{ $store->id }}" style="margin:0;color:#a5b4fc;font-family:'Courier New',monospace;font-size:12px;line-height:1.6;white-space:pre-wrap;word-break:break-word;">{{ $store->getSnippetCode() }}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Add to Product Template -->
    <div class="step-card">
        <span class="step-num">3</span>
        <span class="step-title">Add Button to Product Page</span>
        <div class="step-desc">
            <p style="margin-bottom:12px;">Now add the button to your product page template:</p>

            <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:8px;padding:14px;margin-bottom:14px;">
                <p style="font-size:14px;color:#1e40af;font-weight:600;margin-bottom:8px;">📍 Step-by-step:</p>
                <ol style="font-size:13px;color:#1e3a8a;padding-left:20px;line-height:1.8;">
                    <li>In <strong>Edit code</strong>, find the <strong>Sections</strong> folder</li>
                    <li>Open <code style="background:#fff;padding:2px 8px;border-radius:4px;color:#dc2626;font-weight:700;">main-product.liquid</code> (or <code style="background:#fff;padding:2px 8px;border-radius:4px;color:#dc2626;font-weight:700;">product-template.liquid</code>)</li>
                    <li>Use Ctrl+F (Cmd+F on Mac) and search for: <code style="background:#fff;padding:2px 8px;border-radius:4px;color:#dc2626;">Add to Cart</code> or <code style="background:#fff;padding:2px 8px;border-radius:4px;color:#dc2626;">product-form</code></li>
                    <li>Find the closing tag of the form (look for <code style="background:#fff;padding:2px 8px;border-radius:4px;color:#dc2626;">{%- endform -%}</code> or similar)</li>
                    <li>Paste this single line right AFTER the Add to Cart button:</li>
                </ol>
            </div>

            <div style="margin-top:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong style="font-size:13px;color:var(--g700);">📄 One-Line Render Code:</strong>
                    <button type="button" onclick="copyCode('render-{{ $store->id }}', this)" class="btn btn-primary btn-sm">📋 Copy</button>
                </div>
                <div style="background:#1e1b4b;border-radius:8px;padding:16px;">
                    <pre id="render-{{ $store->id }}" style="margin:0;color:#a5b4fc;font-family:'Courier New',monospace;font-size:13px;">{!! '{%- render \'easzypay-button\' -%}' !!}</pre>
                </div>
            </div>

            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px;margin-top:14px;font-size:13px;color:#166534;">
                💡 <strong>Tip:</strong> Place it AFTER the Shopify "Add to Cart" button so customers see both options.
            </div>
        </div>
    </div>

    <!-- Step 4: Test -->
    <div class="step-card">
        <span class="step-num">4</span>
        <span class="step-title">Test Your Setup</span>
        <div class="step-desc">
            <p style="margin-bottom:12px;">Once you've added both pieces of code, test your checkout:</p>

            <ol style="font-size:14px;color:var(--g700);padding-left:20px;line-height:2;margin-bottom:14px;">
                <li>Visit any product page on your store</li>
                <li>You should see the <strong>"⚡ Buy Now — Secure Checkout"</strong> button below "Add to Cart"</li>
                <li>Click it to test the checkout flow</li>
                <li>Use test card: <code style="background:var(--g100);padding:2px 8px;border-radius:4px;">4242 4242 4242 4242</code></li>
            </ol>

            <a href="https://{{ $store->myshopify_domain }}" target="_blank" class="btn btn-primary" style="margin-right:8px;">
                🏪 Visit Your Store
            </a>
            <a href="{{ route('tenant.customize', $store->id) }}" class="btn" style="background:var(--pu);color:#fff;">
                🎨 Customize Button Style
            </a>
        </div>
    </div>

    <!-- Step 5: Mark as Done -->
    @if(!$store->button_active)
    <div class="step-card" style="background:linear-gradient(135deg,#fef3c7,#fed7aa);border-color:#f59e0b;">
        <span class="step-num" style="background:linear-gradient(135deg,#f59e0b,#d97706);">5</span>
        <span class="step-title">Mark Setup as Complete</span>
        <div class="step-desc">
            <p style="margin-bottom:12px;">After you've successfully tested the button on your store, click below:</p>
            <form method="POST" action="{{ route('tenant.mark-setup-done', $store->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary">✅ Mark Setup Complete</button>
            </form>
        </div>
    </div>
    @else
    <div class="step-card" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-color:#10b981;">
        <span class="step-num" style="background:linear-gradient(135deg,#10b981,#059669);">✓</span>
        <span class="step-title">Setup Complete!</span>
        <div class="step-desc">
            ✅ Your EaszyPay button is live on {{ $store->shop_name }}. Customers can now checkout via Stripe.
        </div>
    </div>
    @endif

</div>

@endforeach

<!-- Store List -->
<div class="tw" style="margin-bottom:20px;">
    <div class="tw-head">
        <div class="tw-title">🏪 Stores</div>
        <button onclick="document.getElementById('add-s').style.display=document.getElementById('add-s').style.display==='none'?'block':'none'" class="btn btn-primary btn-sm">+ Add Store</button>
    </div>
    <div id="add-s" style="display:none;padding:16px 20px;background:var(--g50);border-bottom:1px solid var(--g200);">
        <div style="display:flex;gap:12px;max-width:500px;">
            <input type="text" id="ns" placeholder="yourstore.myshopify.com" style="flex:1;padding:10px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:14px;outline:none;">
            <button onclick="var s=document.getElementById('ns').value.trim();if(!s)return;if(s.indexOf('.myshopify.com')===-1)s+='.myshopify.com';window.location.href='/shopify/install?shop='+encodeURIComponent(s);" class="btn btn-primary">Connect</button>
        </div>
    </div>
    <table>
        <thead><tr><th>Store</th><th>Status</th><th>Button</th><th>Orders</th><th>Revenue</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($stores as $store)
        <tr>
            <td><div style="font-weight:700;">{{ $store->shop_name }}</div><div style="font-size:12px;color:var(--g400);">{{ $store->myshopify_domain }}</div></td>
            <td>@if($store->is_installed)<span class="badge badge-success">✅ Active</span>@else<span class="badge badge-danger">❌ Off</span>@endif</td>
            <td>@if($store->button_active)<span class="badge badge-success">⚡ Live</span>@else<span class="badge badge-warning">Setup Needed</span>@endif</td>
            <td>{{ number_format($store->total_orders) }}</td>
            <td>${{ number_format($store->total_revenue, 2) }}</td>
            <td>
                <a href="{{ route('tenant.customize', $store->id) }}" class="btn btn-primary btn-sm">🎨 Customize</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endif

<!-- Pending Sync -->
<?php
$pendingOrders = \App\Models\CheckoutSession::where('tenant_id', $tenant->id)
    ->where('status', 'paid')
    ->whereNull('shopify_order_id')
    ->orderByDesc('created_at')
    ->limit(20)
    ->get();
?>
@if($pendingOrders->isNotEmpty())
<div class="tw" style="margin-bottom:20px;">
    <div class="tw-head">
        <div class="tw-title">⏳ Pending Shopify Sync ({{ $pendingOrders->count() }})</div>
    </div>
    <table>
        <thead><tr><th>Session</th><th>Customer</th><th>Amount</th><th>Stripe PI</th><th>Paid At</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($pendingOrders as $po)
        <tr>
            <td style="font-family:monospace;font-size:11px;color:var(--p);">{{ substr($po->session_id, 0, 16) }}...</td>
            <td>{{ $po->customer_email ?? '—' }}</td>
            <td style="font-weight:700;">{{ $po->formatted_total }}</td>
            <td style="font-family:monospace;font-size:11px;">{{ substr($po->stripe_payment_intent_id ?? '—', 0, 20) }}...</td>
            <td style="font-size:12px;color:var(--g400);">{{ $po->paid_at ? $po->paid_at->format('M j, g:ia') : $po->created_at->format('M j, g:ia') }}</td>
            <td>
                <form method="POST" action="{{ route('tenant.sync.order', $po->session_id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" onclick="this.disabled=true;this.textContent='Syncing...';this.form.submit();">🔄 Sync Now</button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Recent Orders -->
@if($recentOrders->isNotEmpty())
<div class="tw">
    <div class="tw-head">
        <div class="tw-title">📦 Recent Orders</div>
        <a href="{{ route('tenant.orders') }}" class="btn btn-primary btn-sm">View All →</a>
    </div>
    <table>
        <thead><tr><th>Customer</th><th>Shopify Order</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        @foreach($recentOrders as $order)
        <tr>
            <td>{{ $order->customer_email ?? '—' }}</td>
            <td>@if($order->shopify_order_number)<span style="font-weight:700;color:var(--ok);">{{ $order->shopify_order_number }}</span>@else <span style="color:var(--g400);">—</span> @endif</td>
            <td style="font-weight:700;">{{ $order->formatted_total }}</td>
            <td style="font-size:12px;">
                @if($order->wallet_type === 'apple_pay') 🍎
                @elseif($order->wallet_type === 'google_pay') G
                @else 💳 @endif
                {{ strtoupper($order->card_brand ?? '') }} {{ $order->card_last4 ?? '' }}
            </td>
            <td>
                @if(in_array($order->status, ['completed','shopify_order_created']))
                    <span class="badge badge-success">✅</span>
                @elseif($order->status === 'paid')
                    <span class="badge badge-warning">⏳</span>
                @else
                    <span class="badge badge-gray">{{ ucfirst($order->status) }}</span>
                @endif
            </td>
            <td style="font-size:12px;color:var(--g400);">{{ $order->created_at->format('M j, g:ia') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<script>
function copyCode(elementId, button) {
    var el = document.getElementById(elementId);
    var text = el.textContent || el.innerText;

    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showCopied(button);
        }).catch(function() {
            fallbackCopy(text, button);
        });
    } else {
        fallbackCopy(text, button);
    }
}

function fallbackCopy(text, button) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
        showCopied(button);
    } catch(e) {
        alert('Copy failed. Please select and copy manually.');
    }
    document.body.removeChild(ta);
}

function showCopied(button) {
    var original = button.innerHTML;
    button.innerHTML = '✅ Copied!';
    button.style.background = '#10b981';
    setTimeout(function() {
        button.innerHTML = original;
        button.style.background = '';
    }, 2000);
}
</script>

@endsection