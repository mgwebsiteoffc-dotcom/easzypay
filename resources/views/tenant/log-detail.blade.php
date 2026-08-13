@extends('tenant.layout')
@section('title', 'Transaction')
@section('content')
@php
    $hidden = ['stripe_client_secret', 'password', 'remember_token'];
    $method = $session->wallet_type === 'apple_pay' ? 'Apple Pay'
        : ($session->wallet_type === 'google_pay' ? 'Google Pay'
        : ($session->wallet_type === 'link' ? 'Link' : 'Card'));
    $eventPayloads = [];
    foreach ($paymentLogs as $l) {
        $eventPayloads[(string) $l->id] = [
            'title' => (string) $l->event_type,
            'json'  => json_encode(collect($l->toArray())->except($hidden)->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <a href="{{ route('tenant.logs') }}" class="btn btn-sm btn-ghost">Back</a>
    @if($session->status === 'paid' && !$session->shopify_order_id)
    <form method="POST" action="{{ route('tenant.sync.order', $session->session_id) }}">
        @csrf
        <button type="submit" class="btn btn-sm">Sync to Shopify</button>
    </form>
    @endif
</div>

<div class="tw" style="margin-bottom:16px;">
    <div class="tw-head">
        <div>
            <div class="tw-title">{{ $session->customer_email ?? 'Guest checkout' }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px;">{{ $session->created_at->format('M j, Y · g:i a') }}</div>
        </div>
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
    </div>
    <div class="panel-grid" style="gap:0;margin:0;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
        <div style="padding:18px 20px;border-right:1px solid var(--line);">
            <div class="stat-label">Charged</div>
            <div style="font-size:22px;font-weight:750;letter-spacing:-0.03em;">{{ $session->formatted_total }}</div>
            @if($session->charged_currency && $session->charged_currency !== $session->currency)
            <div class="stat-sub" style="margin-top:4px;">From {{ $session->currency }} @ {{ number_format((float)$session->exchange_rate, 4) }}</div>
            @endif
        </div>
        <div style="padding:18px 20px;border-right:1px solid var(--line);">
            <div class="stat-label">Method</div>
            <div style="font-weight:650;margin-top:4px;">{{ $method }}</div>
            <div class="stat-sub">{{ $session->card_brand ? strtoupper($session->card_brand).' ···· '.$session->card_last4 : '—' }}</div>
        </div>
        <div style="padding:18px 20px;border-right:1px solid var(--line);">
            <div class="stat-label">Shopify</div>
            <div style="font-weight:650;margin-top:4px;">{{ $session->shopify_order_number ?: 'Not synced' }}</div>
            <div class="stat-sub">{{ $session->shop_domain }}</div>
        </div>
        <div style="padding:18px 20px;">
            <div class="stat-label">Events</div>
            <div style="font-size:22px;font-weight:750;">{{ $paymentLogs->count() }}</div>
            <div class="stat-sub">Payment timeline</div>
        </div>
    </div>
</div>

<div class="panel-grid">
    <div class="stat">
        <div class="stat-label">Customer</div>
        <dl class="kv" style="margin-top:12px;">
            <dt>Name</dt><dd>{{ trim(($session->customer_first_name ?? '').' '.($session->customer_last_name ?? '')) ?: '—' }}</dd>
            <dt>Email</dt><dd>{{ $session->customer_email ?: '—' }}</dd>
            <dt>Phone</dt><dd>{{ $session->customer_phone ?: '—' }}</dd>
            <dt>Country</dt><dd>{{ $session->country_code ?: '—' }}</dd>
        </dl>
    </div>
    <div class="stat">
        <div class="stat-label">Delivery</div>
        @if($session->shipping_address1)
        <div style="margin-top:12px;line-height:1.65;font-size:14px;">
            {{ $session->shipping_address1 }}<br>
            @if($session->shipping_address2){{ $session->shipping_address2 }}<br>@endif
            {{ $session->shipping_city }}{{ $session->shipping_state ? ', '.$session->shipping_state : '' }} {{ $session->shipping_zip }}<br>
            {{ $session->shipping_country }}
        </div>
        @else
        <p style="margin-top:12px;color:var(--muted);font-size:14px;">No address captured.</p>
        @endif
    </div>
</div>

@if(!empty($session->items))
<div class="tw">
    <div class="tw-head"><div class="tw-title">Items</div></div>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th></tr></thead>
        <tbody>
        @foreach($session->items as $item)
        <tr>
            <td>
                <div style="font-weight:650;">{{ $item['title'] ?? 'Product' }}</div>
                @if(!empty($item['variant_title']) && $item['variant_title'] !== 'Default Title')
                <div style="font-size:12px;color:var(--muted);">{{ $item['variant_title'] }}</div>
                @endif
            </td>
            <td>{{ $item['quantity'] ?? 1 }}</td>
            <td style="font-weight:650;">${{ number_format((($item['price'] ?? 0) * ($item['quantity'] ?? 1)) / 100, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if($paymentLogs->isNotEmpty())
<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Timeline</div>
    </div>
    <table>
        <thead><tr><th>Event</th><th>Status</th><th>Amount</th><th>Note</th><th>Time</th></tr></thead>
        <tbody>
        @foreach($paymentLogs as $log)
        <tr>
            <td style="font-family:ui-monospace,monospace;font-size:12px;">{{ $log->event_type }}</td>
            <td style="white-space:nowrap;">
                @if(in_array($log->status, ['success','succeeded']))
                    <span class="badge badge-success">{{ $log->status }}</span>
                @elseif($log->status === 'failed')
                    <span class="badge badge-danger">{{ $log->status }}</span>
                @else
                    <span class="badge badge-gray">{{ $log->status }}</span>
                @endif
                <button type="button" class="btn btn-sm btn-ghost js-view-log" data-id="{{ $log->id }}">View log</button>
            </td>
            <td>@if($log->amount){{ number_format($log->amount/100, 2) }} {{ $log->currency }}@else — @endif</td>
            <td style="font-size:12px;color:var(--muted);max-width:220px;">
                @php
                    $note = (string) ($log->error_message ?? '');
                    if (strlen($note) > 80) {
                        $note = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $note), 72);
                    }
                @endphp
                {{ $note !== '' ? $note : '—' }}
            </td>
            <td style="font-size:12px;color:var(--muted);white-space:nowrap;">{{ $log->created_at->format('M j, g:i a') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="modal-bg" id="json-modal">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-h">
            <strong id="json-title">Event log</strong>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-sm btn-ghost" id="json-copy">Copy</button>
                <button type="button" class="btn btn-sm" id="json-close">Close</button>
            </div>
        </div>
        <div class="modal-b"><pre id="log-json"></pre></div>
    </div>
</div>

<script>
(function () {
    var EVENT_LOGS = @json($eventPayloads);
    var modal = document.getElementById('json-modal');
    var titleEl = document.getElementById('json-title');
    var bodyEl = document.getElementById('log-json');

    function openModal(title, text) {
        titleEl.textContent = title || 'Event log';
        bodyEl.textContent = text || '';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-view-log').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = EVENT_LOGS[String(btn.getAttribute('data-id'))];
            if (!row) return;
            openModal(row.title, row.json);
        });
    });

    document.getElementById('json-close').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    document.getElementById('json-copy').addEventListener('click', function () {
        var btn = this;
        var t = bodyEl.textContent || '';
        var done = function () { var o = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = o; }, 1400); };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(t).then(done);
    });
})();
</script>
@endsection
