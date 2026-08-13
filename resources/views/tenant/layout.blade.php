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
            --sidebar-w:232px;--topbar-h:64px;
        }
        html{-webkit-font-smoothing:antialiased}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--paper);color:var(--ink);display:flex;min-height:100vh}
        a{text-decoration:none;color:inherit}
        button,input,select{font-family:inherit}

        .sb{width:var(--sidebar-w);background:var(--ink);color:#f4efe6;position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;z-index:100}
        .sb-logo{padding:22px 22px 18px;font-size:20px;font-weight:800;letter-spacing:-0.03em;border-bottom:1px solid rgba(255,255,255,0.08);font-family:"Iowan Old Style",Palatino,Georgia,serif}
        .sb-logo span{color:#5eead4}
        .sb-nav{padding:16px 12px;flex:1;overflow-y:auto}
        .sb-section{padding:14px 12px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;color:rgba(255,255,255,0.32)}
        .sb-link{display:flex;align-items:center;gap:10px;padding:9px 12px;color:rgba(255,255,255,0.72);font-size:14px;font-weight:500;border-radius:10px;margin:2px 0;transition:background .15s,color .15s;width:100%;border:none;background:none;cursor:pointer;text-align:left}
        .sb-link:hover{background:rgba(255,255,255,0.07);color:#fff}
        .sb-link.active{background:var(--brand);color:#fff}
        .sb-link svg{width:18px;height:18px;flex-shrink:0;opacity:.9}
        .sb-bottom{padding:14px 12px 16px;border-top:1px solid rgba(255,255,255,0.08)}
        .sb-user{padding:4px 10px 10px;font-size:13px;color:rgba(255,255,255,0.55);line-height:1.4}
        .sb-user small{display:block;opacity:.7;font-size:11px;letter-spacing:.04em;text-transform:uppercase;margin-top:3px}

        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
        .topbar{height:var(--topbar-h);background:rgba(246,244,239,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:20px;font-weight:700;font-family:"Iowan Old Style",Palatino,Georgia,serif;letter-spacing:-0.02em}
        .topbar-date{font-size:13px;color:var(--muted)}
        .content{flex:1;padding:28px 32px;width:100%;}

        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(168px,1fr));gap:12px;margin-bottom:24px}
        .stat{background:var(--white);border:1px solid var(--line);border-radius:16px;padding:18px 18px 16px}
        .stat-label{font-size:11px;font-weight:650;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px}
        .stat-val{font-size:26px;font-weight:750;line-height:1;letter-spacing:-0.03em;margin-bottom:6px}
        .stat-sub{font-size:12px;color:var(--gray-400)}
        .stat.success .stat-val,.stat.primary .stat-val{color:var(--brand-2)}
        .stat.danger .stat-val{color:var(--danger)}
        .stat.warning .stat-val{color:var(--warning)}

        .tw{background:var(--white);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-bottom:20px}
        .tw-head{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .tw-title{font-size:15px;font-weight:700}
        table{width:100%;border-collapse:collapse}
        thead th{background:#faf8f3;padding:11px 16px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;text-align:left;border-bottom:1px solid var(--line)}
        tbody td{padding:13px 16px;font-size:14px;color:var(--gray-700);border-bottom:1px solid #f0eee8}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#faf8f3}

        .badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:0.03em}
        .badge-success{background:#e7f6f3;color:#134e4a}
        .badge-danger{background:#fdecea;color:#991b1b}
        .badge-warning{background:#fef3c7;color:#92400e}
        .badge-info{background:#e7f6f3;color:#134e4a}
        .badge-gray{background:var(--gray-100);color:var(--gray-600)}

        .btn{padding:8px 16px;border-radius:999px;font-size:13px;font-weight:650;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;font-family:inherit;background:var(--ink);color:#fff}
        .btn:hover{background:var(--brand-2)}
        .btn-primary{background:var(--ink);color:#fff}
        .btn-ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
        .btn-ghost:hover{background:#faf8f3}
        .btn-sm{padding:6px 12px;font-size:12px}

        .alert{padding:12px 16px;border-radius:12px;font-size:14px;margin-bottom:18px;line-height:1.5}
        .alert-success{background:#e7f6f3;color:#134e4a;border:1px solid #b7e0d8}
        .alert-danger{background:#fdecea;color:#991b1b;border:1px solid #f5c2c0}
        .alert-info{background:#eef2f1;color:#134e4a;border:1px solid #c5ddd8}
        .alert-warning{background:#fef3c7;color:#92400e;border:1px solid #fde68a}

        .code-block{background:var(--ink);color:#99f6e4;border-radius:12px;padding:16px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;line-height:1.7;overflow-x:auto;margin:12px 0}
        .cta-box{background:var(--brand-2);border-radius:20px;padding:40px 32px;text-align:center;color:#fff;margin-bottom:24px}
        .cta-box h2{font-size:24px;font-weight:700;margin-bottom:8px;font-family:"Iowan Old Style",Palatino,Georgia,serif;letter-spacing:-0.02em}
        .cta-box p{opacity:0.8;font-size:15px;margin-bottom:22px}
        .form-inline{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;max-width:480px;margin:0 auto}
        .form-inline input{flex:1;padding:13px 16px;border-radius:999px;border:none;font-size:14px;min-width:200px}
        .form-inline button{padding:13px 22px;background:#fff;color:var(--brand-2);border:none;border-radius:999px;font-weight:700;font-size:14px;cursor:pointer}

        .step-card{background:var(--white);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:12px}
        .step-num{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--ink);color:white;border-radius:50%;font-weight:700;font-size:13px;margin-right:10px}
        .step-title{font-size:15px;font-weight:700;vertical-align:middle}
        .step-desc{margin-top:12px;color:var(--muted);font-size:14px;line-height:1.7;padding-left:38px}
        .note{border-radius:12px;padding:14px 16px;margin:12px 0;font-size:13px;line-height:1.7}
        .note-warn{background:#fff8e8;border:1px solid #f3e0a8;color:#7a5b12}
        .note-info{background:#f3f6f5;border:1px solid #d5e0dc;color:#2d3b38}
        .note-ok{background:#e7f6f3;border:1px solid #b7e0d8;color:#134e4a}
        code{background:#fff;border:1px solid var(--line);padding:1px 6px;border-radius:6px;font-size:12px;color:var(--ink)}

        @media(max-width:768px){
            .sb{transform:translateX(-100%)}
            .main{margin-left:0}
            .stats{grid-template-columns:repeat(2,1fr)}
            .content{padding:18px}
        }
    </style>
</head>
<body>
<aside class="sb">
    <a class="sb-logo" href="/">Easzy<span>Pay</span></a>
    <nav class="sb-nav">
        <div class="sb-section">Dashboard</div>
        <a href="/app/dashboard" class="sb-link {{ request()->routeIs('tenant.dashboard*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
            Overview
        </a>
        <div class="sb-section">Store</div>
        <a href="/app/stores" class="sb-link {{ request()->routeIs('tenant.stores*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 9h16l-1 11H5L4 9z"/><path d="M8 9V7a4 4 0 018 0v2"/></svg>
            My Stores
        </a>
        <a href="/app/orders" class="sb-link {{ request()->routeIs('tenant.orders*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10l2 4H5l2-4z"/><path d="M5 8h14v11a1 1 0 01-1 1H6a1 1 0 01-1-1V8z"/><path d="M9 12h6"/></svg>
            Orders
        </a>
        <a href="/app/logs" class="sb-link {{ request()->routeIs('tenant.logs*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 6h11M8 12h11M8 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/></svg>
            Transaction Logs
        </a>
        <div class="sb-section">Account</div>
        <a href="/app/settings" class="sb-link {{ request()->routeIs('tenant.settings*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
            Settings
        </a>
    </nav>
    <div class="sb-bottom">
        <div class="sb-user">
            {{ Auth::guard('tenant')->user()->name ?? 'Merchant' }}
            <small>{{ ucfirst(Auth::guard('tenant')->user()->plan ?? 'trial') }} plan</small>
        </div>
        <form method="POST" action="/app/logout">
            @csrf
            <button type="submit" class="sb-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H6a2 2 0 01-2-2V5a2 2 0 012-2h3"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Log out
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
