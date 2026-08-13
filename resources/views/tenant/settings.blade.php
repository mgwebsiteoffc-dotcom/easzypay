@extends('tenant.layout')
@section('title', 'Settings')
@section('content')
@php
    $trialDays = 0;
    if ($tenant->trial_ends_at) {
        $trialDays = (int) max(0, ceil(now()->floatDiffInDays($tenant->trial_ends_at, false)));
    }
@endphp
<div class="panel-grid">
    <div class="stat">
        <div class="stat-label">Account</div>
        <dl class="kv" style="margin-top:14px;">
            <dt>Name</dt><dd>{{ $tenant->name }}</dd>
            <dt>Email</dt><dd>{{ $tenant->email }}</dd>
            <dt>Company</dt><dd>{{ $tenant->company_name ?: '—' }}</dd>
            <dt>Joined</dt><dd>{{ $tenant->created_at->format('M j, Y') }}</dd>
        </dl>
    </div>
    <div class="stat">
        <div class="stat-label">Plan</div>
        <div style="margin-top:14px;font-size:22px;font-weight:750;letter-spacing:-0.03em;">{{ ucfirst($tenant->plan) }}</div>
        @if($tenant->isOnTrial())
        <p style="margin-top:8px;color:var(--muted);font-size:14px;">Trial · {{ $trialDays }} {{ $trialDays === 1 ? 'day' : 'days' }} left</p>
        @elseif($tenant->isTrialExpired())
        <p style="margin-top:8px;color:var(--danger);font-size:14px;">Trial ended. Upgrade to keep charging.</p>
        @endif
    </div>
    <div class="stat" style="grid-column:1 / -1;">
        <div class="stat-label">Payments</div>
        <p style="margin-top:12px;font-size:14px;line-height:1.6;color:var(--muted);">Stripe is connected on the platform. Checkout charges in the shopper’s currency and writes paid orders back to Shopify.</p>
    </div>
</div>
@endsection
