@extends('tenant.layout')
@section('title', 'My Stores')
@section('content')
<div class="tw">
    <div class="tw-head">
        <div class="tw-title">🏪 Your Stores</div>
        <button onclick="document.getElementById('add-form').style.display='block'" class="btn btn-primary btn-sm">+ Add Store</button>
    </div>
    <div id="add-form" style="display:none;padding:16px 20px;background:var(--g50);border-bottom:1px solid var(--g200);">
        <div style="display:flex;gap:12px;max-width:500px;">
            <input type="text" id="shop-d" placeholder="yourstore.myshopify.com" style="flex:1;padding:10px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:14px;outline:none;">
            <button onclick="var s=document.getElementById('shop-d').value.trim();if(!s)return;if(s.indexOf('.myshopify.com')===-1)s+='.myshopify.com';window.location.href='/shopify/install?shop='+encodeURIComponent(s);" class="btn btn-primary">Connect →</button>
        </div>
    </div>
    <table>
        <thead><tr><th>Store</th><th>Domain</th><th>Plan</th><th>Status</th><th>Button</th><th>Orders</th><th>Revenue</th><th>Synced</th></tr></thead>
        <tbody>
            @forelse($stores as $store)
            <tr>
                <td style="font-weight:700;">{{ $store->shop_name }}</td>
                <td style="font-size:12px;color:var(--g500);">{{ $store->myshopify_domain }}</td>
                <td>{{ $store->shopify_plan ?? '—' }}</td>
                <td>@if($store->is_installed)<span class="badge badge-success">✅ Active</span>@else<span class="badge badge-danger">❌ Off</span>@endif</td>
                <td>@if($store->button_active)<span class="badge badge-success">⚡ Live</span>@else<span class="badge badge-warning">Setup</span>@endif</td>
                <td>
    <a href="{{ route('tenant.customize', $store->id) }}" class="btn btn-primary btn-sm">
        🎨 Customize
    </a>
</td>
                <td>{{ number_format($store->total_orders) }}</td>
                <td>${{ number_format($store->total_revenue, 2) }}</td>
                <td style="font-size:12px;color:var(--g400);">{{ $store->last_synced_at ? $store->last_synced_at->diffForHumans() : 'Never' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;color:var(--g400);padding:40px;">No stores connected</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection