<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EaszyPay Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #0f766e;
            --primary-dk: #134e4a;
            --sidebar-w:  240px;
            --header-h:   64px;
            --success:    #0f766e;
            --danger:     #b42318;
            --warning:    #b45309;
            --gray-50:    #f6f4ef;
            --gray-100:   #efece4;
            --gray-200:   #e6e9f0;
            --gray-500:   #5b6578;
            --gray-700:   #374151;
            --gray-900:   #0b1220;
        }

        html { -webkit-font-smoothing: antialiased; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            display: flex;
            min-height: 100vh;
        }

        /* ---- Sidebar ---- */
        .sidebar {
            width: var(--sidebar-w);
            background: #0b1220;
            color: #f4efe6;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 20px 20px 16px;
            font-size: 20px;
            font-weight: 800;
            color: #f4efe6;
            letter-spacing: -0.03em;
            font-family: "Iowan Old Style", Palatino, Georgia, serif;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-logo span { color: #5eead4; }

        .sidebar-section {
            padding: 16px 12px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.35);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.15s;
        }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }

        .sidebar-link.active {
            background: var(--primary);
            color: white;
        }

        .sidebar-link .icon { font-size: 16px; width: 20px; text-align: center; }

        /* ---- Main ---- */
        .main-area {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ---- Top Bar ---- */
        .topbar {
            height: var(--header-h);
            background: rgba(246,244,239,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-user {
            font-size: 14px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .topbar-logout {
            padding: 6px 14px;
            background: var(--gray-100);
            color: var(--gray-700);
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        .topbar-logout:hover { background: var(--gray-200); }

        /* ---- Content ---- */
        .content {
            flex: 1;
            padding: 28px;
        }

        /* ---- Stats Grid ---- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-sub {
            font-size: 12px;
            color: var(--gray-400);
        }

        .stat-card.primary .stat-value { color: var(--primary); }
        .stat-card.success .stat-value { color: var(--success); }
        .stat-card.danger  .stat-value { color: var(--danger); }
        .stat-card.warning .stat-value { color: var(--warning); }

        /* ---- Table ---- */
        .qp-table-wrap {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .qp-table-head {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .qp-table-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-900);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--gray-50);
            padding: 11px 16px;
            font-size: 12px;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        tbody td {
            padding: 13px 16px;
            font-size: 14px;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--gray-50); }

        /* ---- Badges ---- */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info    { background: #dbeafe; color: #1e40af; }
        .badge-gray    { background: var(--gray-100); color: var(--gray-600); }
        .badge-purple  { background: #ede9fe; color: #5b21b6; }

        /* ---- Filters ---- */
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-input {
            padding: 7px 12px;
            border: 1.5px solid var(--gray-200);
            border-radius: 7px;
            font-size: 13px;
            color: var(--gray-700);
            outline: none;
            transition: border-color 0.15s;
            background: white;
        }

        .filter-input:focus { border-color: var(--primary); }

        .btn {
            padding: 7px 16px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dk); }
        .btn-danger  { background: var(--danger); color: white; }
        .btn-sm      { padding: 4px 10px; font-size: 12px; }

        /* ---- Alerts ---- */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* ---- Chart ---- */
        .chart-wrap {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 16px;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 120px;
        }

        .chart-bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            gap: 4px;
        }

        .chart-bar-inner {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: flex-end;
        }

        .chart-bar {
            width: 100%;
            background: var(--primary);
            border-radius: 4px 4px 0 0;
            min-height: 4px;
            transition: height 0.3s ease;
        }

        .chart-bar-label {
            font-size: 10px;
            color: var(--gray-400);
            text-align: center;
        }

        .chart-bar-val {
            font-size: 10px;
            color: var(--gray-600);
            font-weight: 600;
        }

        /* ---- Pagination ---- */
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            padding: 16px;
        }

        .page-link {
            padding: 6px 12px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            font-size: 13px;
            color: var(--gray-700);
            text-decoration: none;
            transition: all 0.15s;
        }

        .page-link:hover { border-color: var(--primary); color: var(--primary); }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-area { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        Easzy<span>Pay</span>
        <span style="font-size:10px;background:rgba(255,255,255,0.15);padding:2px 6px;border-radius:4px;margin-left:4px;font-family:sans-serif;">Admin</span>
    </div>

    <div style="padding: 12px 8px; flex: 1;">
        <div class="sidebar-section">Overview</div>

        <a href="{{ route('admin.dashboard') }}"
           class="sidebar-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
            <span class="icon">📊</span> Dashboard
        </a>

        <div class="sidebar-section">Payments</div>

        <a href="{{ route('admin.orders') }}"
           class="sidebar-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
            <span class="icon">📦</span> Orders
        </a>

        <a href="{{ route('admin.orders', ['status' => 'paid']) }}"
           class="sidebar-link">
            <span class="icon">⏳</span> Pending Sync
        </a>

        <a href="{{ route('admin.orders', ['status' => 'failed']) }}"
           class="sidebar-link">
            <span class="icon">❌</span> Failed
        </a>

        <div class="sidebar-section">System</div>

        <a href="{{ route('admin.webhooks') }}"
           class="sidebar-link {{ request()->routeIs('admin.webhooks*') ? 'active' : '' }}">
            <span class="icon">📡</span> Webhooks
        </a>

        <a href="{{ route('admin.webhooks', ['status' => 'failed']) }}"
           class="sidebar-link">
            <span class="icon">⚠️</span> Failed Webhooks
        </a>
    </div>

    <!-- Bottom section -->
    <div style="padding: 12px 8px; border-top: 1px solid rgba(255,255,255,0.08);">
        <div style="padding: 10px 16px; font-size: 13px; color: rgba(255,255,255,0.5);">
            {{ Auth::guard('admin')->user()->name ?? 'Admin' }}
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="sidebar-link" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                <span class="icon">🚪</span> Logout
            </button>
        </form>
    </div>
</aside>

<!-- Main Area -->
<div class="main-area">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-actions">
            <span class="topbar-user">
                {{ now()->format('D, M j Y') }}
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="content">

        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">❌ {{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</div>

@yield('scripts')

</body>
</html>