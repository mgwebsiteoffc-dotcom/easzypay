<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EaszyPay</title>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --ink:#0b1220;--muted:#5b6578;--line:#e6e9f0;--paper:#f6f4ef;--white:#fff;
            --brand:#0f766e;--brand-2:#134e4a;
            --primary:#0f766e;--primary-dk:#134e4a;--purple:#134e4a;
            --success:#0f766e;--danger:#b42318;--warning:#b45309;
            --gray-50:#f6f4ef;--gray-100:#efece4;--gray-200:#e6e9f0;--gray-400:#9aa3b2;
            --gray-500:#5b6578;--gray-600:#5b6578;--gray-700:#374151;--gray-900:#0b1220;
            --sidebar-w:240px;--topbar-h:64px;
        }
        html{-webkit-font-smoothing:antialiased}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--paper);color:var(--ink);display:flex;min-height:100vh}
        a{text-decoration:none;color:inherit}

        .sb{width:var(--sidebar-w);background:var(--ink);color:#f4efe6;position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;z-index:100;overflow-y:auto}
        .sb-logo{padding:20px;font-size:20px;font-weight:800;letter-spacing:-0.03em;border-bottom:1px solid rgba(255,255,255,0.08);font-family:"Iowan Old Style",Palatino,Georgia,serif}
        .sb-logo span{color:#5eead4}
        .sb-section{padding:16px 12px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.32)}
        .sb-link{display:flex;align-items:center;gap:10px;padding:10px 16px;color:rgba(255,255,255,0.72);font-size:14px;font-weight:500;border-radius:10px;margin:2px 8px;transition:all 0.15s}
        .sb-link:hover{background:rgba(255,255,255,0.08);color:#fff}
        .sb-link.active{background:var(--brand);color:#fff}
        .sb-link .icon{font-size:16px;width:20px;text-align:center}
        .sb-bottom{padding:12px 8px;border-top:1px solid rgba(255,255,255,0.08);margin-top:auto}
        .sb-user{padding:10px 16px;font-size:13px;color:rgba(255,255,255,0.5)}

        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
        .topbar{height:var(--topbar-h);background:rgba(246,244,239,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:18px;font-weight:700;font-family:"Iowan Old Style",Palatino,Georgia,serif;letter-spacing:-0.02em}
        .topbar-date{font-size:13px;color:var(--muted)}
        .content{flex:1;padding:28px}

        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
        .stat{background:var(--white);border:1px solid var(--line);border-radius:16px;padding:20px;box-shadow:0 10px 30px rgba(15,23,42,0.04)}
        .stat-label{font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px}
        .stat-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:6px}
        .stat-sub{font-size:12px;color:var(--gray-400)}
        .stat.success .stat-val{color:var(--brand)}
        .stat.primary .stat-val{color:var(--brand-2)}
        .stat.danger .stat-val{color:var(--danger)}
        .stat.warning .stat-val{color:var(--warning)}

        .tw{background:var(--white);border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.04);margin-bottom:20px}
        .tw-head{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .tw-title{font-size:15px;font-weight:700}
        table{width:100%;border-collapse:collapse}
        thead th{background:#faf8f3;padding:11px 16px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.04em;text-align:left;border-bottom:1px solid var(--line)}
        tbody td{padding:13px 16px;font-size:14px;color:var(--gray-700);border-bottom:1px solid #f0eee8}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#faf8f3}

        .badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em}
        .badge-success{background:#e7f6f3;color:#134e4a}
        .badge-danger{background:#fdecea;color:#991b1b}
        .badge-warning{background:#fef3c7;color:#92400e}
        .badge-info{background:#e7f6f3;color:#134e4a}
        .badge-gray{background:var(--gray-100);color:var(--gray-600)}

        .btn{padding:8px 16px;border-radius:999px;font-size:13px;font-weight:650;cursor:pointer;border:none;transition:all 0.15s;display:inline-flex;align-items:center;gap:6px;text-decoration:none;font-family:inherit}
        .btn-primary{background:var(--ink);color:#fff}
        .btn-primary:hover{background:var(--brand-2)}
        .btn-sm{padding:5px 12px;font-size:12px}

        .alert{padding:14px 18px;border-radius:14px;font-size:14px;margin-bottom:20px;line-height:1.5}
        .alert-success{background:#e7f6f3;color:#134e4a;border:1px solid #b7e0d8}
        .alert-danger{background:#fdecea;color:#991b1b;border:1px solid #f5c2c0}
        .alert-info{background:#eef2ff;color:#1e3a5f;border:1px solid #c7d2fe}
        .alert-warning{background:#fef3c7;color:#92400e;border:1px solid #fde68a}

        .code-block{background:var(--ink);color:#99f6e4;border-radius:14px;padding:20px;font-family:'Courier New',monospace;font-size:13px;line-height:1.8;overflow-x:auto;margin:12px 0;position:relative}
        .code-block .copy-btn{position:absolute;top:10px;right:10px;background:rgba(255,255,255,0.1);color:white;border:none;padding:5px 12px;border-radius:999px;font-size:11px;cursor:pointer;font-weight:600}
        .code-block .copy-btn:hover{background:rgba(255,255,255,0.2)}

        .cta-box{background:var(--brand-2);border-radius:22px;padding:36px;text-align:center;color:#fff;margin-bottom:24px}
        .cta-box h2{font-size:22px;font-weight:700;margin-bottom:12px;font-family:"Iowan Old Style",Palatino,Georgia,serif}
        .cta-box p{opacity:0.85;font-size:15px;margin-bottom:24px}

        .form-inline{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;max-width:500px;margin:0 auto}
        .form-inline input{flex:1;padding:14px 18px;border-radius:12px;border:none;font-size:15px;min-width:200px}
        .form-inline button{padding:14px 28px;background:#fff;color:var(--brand-2);border:none;border-radius:999px;font-weight:700;font-size:15px;cursor:pointer}

        .step-card{background:var(--white);border:1px solid var(--line);border-radius:16px;padding:24px;margin-bottom:16px}
        .step-num{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:var(--ink);color:white;border-radius:50%;font-weight:800;font-size:14px;margin-right:12px}
        .step-title{font-size:16px;font-weight:700;display:inline;vertical-align:middle}
        .step-desc{margin-top:12px;color:var(--muted);font-size:14px;line-height:1.7;padding-left:44px}

        @media(max-width:768px){
            .sb{transform:translateX(-100%)}
            .main{margin-left:0}
            .stats{grid-template-columns:repeat(2,1fr)}
        }
    </style>
</head>
<body>

<aside class="sb">
    <a class="sb-logo" href="/">Easzy<span>Pay</span></a>

<div style="padding:12px 8px;flex:1;">
    <div class="sb-section">Dashboard</div>
    <a href="/app/dashboard" class="sb-link {{ request()->routeIs('tenant.dashboard*') ? 'active' : '' }}">
        <span class="icon">📊</span> Overview
    </a>

    <div class="sb-section">Store</div>
    <a href="/app/stores" class="sb-link {{ request()->routeIs('tenant.stores*') ? 'active' : '' }}">
        <span class="icon">🏪</span> My Stores
    </a>
    <a href="/app/orders" class="sb-link {{ request()->routeIs('tenant.orders*') ? 'active' : '' }}">
        <span class="icon">📦</span> Orders
    </a>
    <a href="/app/logs" class="sb-link {{ request()->routeIs('tenant.logs*') ? 'active' : '' }}">
        <span class="icon">📋</span> Transaction Logs
    </a>

    <div class="sb-section">Account</div>
    <a href="/app/settings" class="sb-link {{ request()->routeIs('tenant.settings*') ? 'active' : '' }}">
        <span class="icon">⚙️</span> Settings
    </a>
</div>

<div class="sb-bottom">
    <div class="sb-user">
        {{ Auth::guard('tenant')->user()->name ?? 'Merchant' }}<br>
        <small style="opacity:0.6;">{{ ucfirst(Auth::guard('tenant')->user()->plan ?? 'trial') }} Plan</small>
    </div>
    <form method="POST" action="/app/logout">
        @csrf
        <button type="submit" class="sb-link" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
            <span class="icon">🚪</span> Logout
        </button>
    </form>
</div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">@yield('title', 'Dashboard')</div>
        <div class="topbar-date">{{ now()->format('D, M j Y') }}</div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('install_success'))
            <div class="alert alert-success">
                <strong>{{ session('store_name', 'Your store') }}</strong> is connected. Finish the setup steps below.
            </div>
        @endif

        @yield('content')
    </div>
</div>

@yield('scripts')
</body>
</html>
