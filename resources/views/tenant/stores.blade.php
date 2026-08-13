@extends('tenant.layout')
@section('title', 'My Stores')
@section('content')
<div class="tw">
    <div class="tw-head">
        <div class="tw-title">Stores</div>
        <button type="button" onclick="document.getElementById('add-form').style.display=document.getElementById('add-form').style.display==='none'?'block':'none'" class="btn btn-sm">Add store</button>
    </div>
    <div id="add-form" style="display:none;padding:16px 20px;background:#faf8f3;border-bottom:1px solid var(--line);">
        <div style="display:flex;gap:10px;max-width:520px;flex-wrap:wrap;">
            <input class="fld" type="text" id="shop-d" placeholder="yourstore.myshopify.com" style="flex:1;min-width:220px;border-radius:12px;">
            <button type="button" class="btn" onclick="var s=document.getElementById('shop-d').value.trim();if(!s)return;if(s.indexOf('.myshopify.com')===-1)s+='.myshopify.com';window.location.href='/shopify/install?shop='+encodeURIComponent(s);">Connect</button>
        </div>
    </div>
    <table>
        <thead><tr><th>Store</th><th>Plan</th><th>Status</th><th>Button</th><th>Orders</th><th>Revenue</th><th></th></tr></thead>
        <tbody>
            @forelse($stores as $store)
            <tr>
                <td>
                    <div style="font-weight:700;">{{ $store->shop_name }}</div>
                    <div style="font-size:12px;color:var(--muted);">{{ $store->myshopify_domain }}</div>
                </td>
                <td>{{ $store->shopify_plan ?? '—' }}</td>
                <td>@if($store->is_installed)<span class="badge badge-success">Active</span>@else<span class="badge badge-danger">Off</span>@endif</td>
                <td>@if($store->button_active)<span class="badge badge-success">Live</span>@else<span class="badge badge-warning">Setup</span>@endif</td>
                <td>{{ number_format($store->total_orders) }}</td>
                <td>${{ number_format($store->total_revenue, 2) }}</td>
                <td><a href="{{ route('tenant.customize', $store->id) }}" class="btn btn-sm btn-ghost">Customize</a></td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">No stores connected</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
