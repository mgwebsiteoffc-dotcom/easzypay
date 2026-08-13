@extends('tenant.layout')
@section('title', 'Settings')
@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="stat">
        <div class="stat-label" style="margin-bottom:16px;">👤 Account</div>
        @foreach(['Name'=>$tenant->name,'Email'=>$tenant->email,'Company'=>$tenant->company_name,'Plan'=>ucfirst($tenant->plan),'Joined'=>$tenant->created_at->format('M j, Y')] as $l=>$v)
        <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
            <span style="font-size:13px;color:var(--g400);min-width:80px;">{{ $l }}</span>
            <span style="font-size:14px;font-weight:600;">{{ $v ?? '—' }}</span>
        </div>
        @endforeach
    </div>
    <div class="stat">
        <div class="stat-label" style="margin-bottom:16px;">💳 Subscription</div>
        <div style="text-align:center;padding:20px 0;">
            <div style="font-weight:700;color:var(--p);text-transform:uppercase;">{{ ucfirst($tenant->plan) }} Plan</div>
            @if($tenant->isOnTrial())<div style="margin-top:8px;font-size:13px;color:var(--warning);">⏳ Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}</div>@endif
            @if($tenant->isTrialExpired())<div style="margin-top:8px;font-size:13px;color:var(--er);">⚠️ Trial expired</div>@endif
        </div>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <div class="stat-label" style="margin-bottom:16px;">🔑 Stripe</div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;font-size:13px;color:#166534;">✅ Stripe connected and active</div>
    </div>
</div>
@endsection