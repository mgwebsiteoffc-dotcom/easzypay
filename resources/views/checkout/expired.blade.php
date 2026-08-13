@extends('layouts.checkout')

@section('title', 'Session Expired')

@section('content')
<div style="max-width:480px;margin:80px auto;padding:40px 20px;text-align:center;">
    <div style="font-size:64px;margin-bottom:16px;">⏰</div>
    <h2 style="color:#111827;font-size:24px;margin-bottom:12px;">Session Expired</h2>
    <p style="color:#6b7280;line-height:1.7;margin-bottom:28px;">
        This checkout session has expired or is invalid.<br>
        Please go back to the store and try again.
    </p>
    <button
        onclick="history.back()"
        style="
            padding:14px 32px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white;border:none;border-radius:10px;
            font-size:15px;font-weight:700;cursor:pointer;
        "
    >
        ← Go Back to Store
    </button>
</div>
@endsection