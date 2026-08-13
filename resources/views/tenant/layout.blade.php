<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EaszyPay</title>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--primary:#667eea;--primary-dk:#5a67d8;--purple:#764ba2;--success:#10b981;--danger:#ef4444;--warning:#f59e0b;--gray-50:#f9fafb;--gray-100:#f3f4f6;--gray-200:#e5e7eb;--gray-400:#9ca3af;--gray-500:#6b7280;--gray-700:#374151;--gray-900:#111827;--sidebar-w:240px;--topbar-h:60px}
        html{-webkit-font-smoothing:antialiased}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--gray-50);color:var(--gray-900);display:flex;min-height:100vh}
        a{text-decoration:none;color:inherit}

        /* Sidebar */
        .sb{width:var(--sidebar-w);background:#1e1b4b;color:white;position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;z-index:100;overflow-y:auto}
        .sb-logo{padding:20px;font-size:18px;font-weight:800;color:#a5b4fc;border-bottom:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:8px}
        .sb-section{padding:16px 12px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.3)}
        .sb-link{display:flex;align-items:center;gap:10px;padding:10px 16px;color:rgba(255,255,255,0.7);font-size:14px;font-weight:500;border-radius:8px;margin:2px 8px;transition:all 0.15s}
        .sb-link:hover{background:rgba(255,255,255,0.08);color:white}
        .sb-link.active{background:var(--primary);color:white}
        .sb-link .icon{font-size:16px;width:20px;text-align:center}
        .sb-bottom{padding:12px 8px;border-top:1px solid rgba(255,255,255,0.08);margin-top:auto}
        .sb-user{padding:10px 16px;font-size:13px;color:rgba(255,255,255,0.5)}

        /* Main */
        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
        .topbar{height:var(--topbar-h);background:white;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:16px;font-weight:700}
        .topbar-date{font-size:14px;color:var(--gray-500)}
        .content{flex:1;padding:28px}

        /* Stats */
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
        .stat{background:white;border:1px solid var(--gray-200);border-radius:10px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,0.05)}
        .stat-label{font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px}
        .stat-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:6px}
        .stat-sub{font-size:12px;color:var(--gray-400)}
        .stat.success .stat-val{color:var(--success)}
        .stat.primary .stat-val{color:var(--primary)}
        .stat.danger .stat-val{color:var(--danger)}
        .stat.warning .stat-val{color:var(--warning)}

        /* Table */
        .tw{background:white;border:1px solid var(--gray-200);border-radius:10px;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,0.05);margin-bottom:20px}
        .tw-head{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .tw-title{font-size:15px;font-weight:700}
        table{width:100%;border-collapse:collapse}
        thead th{background:var(--gray-50);padding:11px 16px;font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.04em;text-align:left;border-bottom:1px solid var(--gray-200)}
        tbody td{padding:13px 16px;font-size:14px;color:var(--gray-700);border-bottom:1px solid var(--gray-100)}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:var(--gray-50)}

        /* Badge */
        .badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em}
        .badge-success{background:#d1fae5;color:#065f46}
        .badge-danger{background:#fee2e2;color:#991b1b}
        .badge-warning{background:#fef3c7;color:#92400e}
        .badge-info{background:#dbeafe;color:#1e40af}
        .badge-gray{background:var(--gray-100);color:var(--gray-600)}

        /* Buttons */
        .btn{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all 0.15s;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
        .btn-primary{background:var(--primary);color:white}
        .btn-primary:hover{background:var(--primary-dk)}
        .btn-sm{padding:5px 12px;font-size:12px}

        /* Alert */
        .alert{padding:14px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;line-height:1.5}
        .alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
        .alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .alert-info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
        .alert-warning{background:#fef3c7;color:#92400e;border:1px solid #fde68a}

        /* Code block */
        .code-block{background:#1e1b4b;color:#a5b4fc;border-radius:10px;padding:20px;font-family:'Courier New',monospace;font-size:13px;line-height:1.8;overflow-x:auto;margin:12px 0;position:relative}
        .code-block .copy-btn{position:absolute;top:10px;right:10px;background:rgba(255,255,255,0.1);color:white;border:none;padding:5px 12px;border-radius:6px;font-size:11px;cursor:pointer;font-weight:600}
        .code-block .copy-btn:hover{background:rgba(255,255,255,0.2)}

        /* CTA Box */
        .cta-box{background:linear-gradient(135deg,var(--primary),var(--purple));border-radius:16px;padding:36px;text-align:center;color:white;margin-bottom:24px}
        .cta-box h2{font-size:22px;font-weight:700;margin-bottom:12px}
        .cta-box p{opacity:0.85;font-size:15px;margin-bottom:24px}

        .form-inline{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;max-width:500px;margin:0 auto}
        .form-inline input{flex:1;padding:14px 18px;border-radius:10px;border:none;font-size:15px;min-width:200px}
        .form-inline button{padding:14px 28px;background:white;color:var(--primary);border:none;border-radius:10px;font-weight:700;font-size:15px;cursor:pointer}

        /* Steps */
        .step-card{background:white;border:1px solid var(--gray-200);border-radius:12px;padding:24px;margin-bottom:16px}
        .step-num{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:linear-gradient(135deg,var(--primary),var(--purple));color:white;border-radius:8px;font-weight:800;font-size:14px;margin-right:12px}
        .step-title{font-size:16px;font-weight:700;display:inline;vertical-align:middle}
        .step-desc{margin-top:12px;color:var(--gray-500);font-size:14px;line-height:1.7;padding-left:44px}

        @media(max-width:768px){
            .sb{transform:translateX(-100%)}
            .main{margin-left:0}
            .stats{grid-template-columns:repeat(2,1fr)}
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sb">
    <div class="sb-logo">⚡ EaszyPay</div>

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

<!-- Main -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title">@yield('title', 'Dashboard')</div>
        <div class="topbar-date">{{ now()->format('D, M j Y') }}</div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">❌ {{ session('error') }}</div>
        @endif
        @if(session('install_success'))
            <div class="alert alert-success">
                🎉 <strong>{{ session('store_name', 'Your store') }}</strong> has been connected successfully!
                Follow the setup steps below to complete installation.
            </div>
        @endif

        @yield('content')
    </div>
</div>

@yield('scripts')
</body>
</html>