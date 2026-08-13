<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Secure Checkout') — EaszyPay</title>

    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>

    <style>
        /* ======================================================
           RESET & BASE
        ====================================================== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #0f766e;
            --primary-dk: #134e4a;
            --success:    #10b981;
            --danger:     #ef4444;
            --warning:    #f59e0b;
            --gray-50:    #f9fafb;
            --gray-100:   #f3f4f6;
            --gray-200:   #e5e7eb;
            --gray-300:   #d1d5db;
            --gray-400:   #9ca3af;
            --gray-500:   #6b7280;
            --gray-600:   #4b5563;
            --gray-700:   #374151;
            --gray-800:   #1f2937;
            --gray-900:   #111827;
            --radius:     10px;
            --shadow-sm:  0 1px 2px rgba(0,0,0,0.05);
            --shadow:     0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md:  0 4px 6px rgba(0,0,0,0.07);
        }

        html {
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                         Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            line-height: 1.5;
        }

        /* ======================================================
           HEADER
        ====================================================== */
        .qp-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: var(--shadow-sm);
        }

        .qp-logo {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0b1220;
            font-family: "Iowan Old Style", Palatino, Georgia, serif;
            text-decoration: none;
        }

        .qp-secure {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--success);
        }

        /* ======================================================
           LAYOUT
        ====================================================== */
        .qp-layout {
            max-width: 1060px;
            margin: 0 auto;
            padding: 32px 16px 64px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 28px;
            align-items: start;
        }

        @media (max-width: 840px) {
            .qp-layout {
                grid-template-columns: 1fr;
                padding: 16px 12px 48px;
            }
            .qp-order-col { order: -1; }
        }

        /* ======================================================
           CARDS
        ====================================================== */
        .qp-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
        }

        .qp-card:last-child { margin-bottom: 0; }

        .qp-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ======================================================
           FORMS
        ====================================================== */
        .qp-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media (max-width: 480px) {
            .qp-form-row { grid-template-columns: 1fr; }
        }

        .qp-form-group { margin-bottom: 14px; }
        .qp-form-group:last-child { margin-bottom: 0; }

        .qp-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .qp-label .req { color: var(--danger); margin-left: 2px; }

        .qp-input,
        .qp-select {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1.5px solid var(--gray-300);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            color: var(--gray-900);
            background: white;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            appearance: none;
        }

        .qp-input:focus,
        .qp-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.12);
        }

        .qp-input.is-invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239,68,68,0.08);
        }

        .qp-input.is-valid {
            border-color: var(--success);
        }

        .qp-input::placeholder { color: var(--gray-400); }

        .qp-select-wrap { position: relative; }
        .qp-select-wrap::after {
            content: '▾';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
            font-size: 13px;
        }
        .qp-select-wrap .qp-select { padding-right: 38px; cursor: pointer; }

        /* Field hint */
        .qp-field-hint {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 4px;
        }

        .qp-field-error {
            font-size: 12px;
            color: var(--danger);
            margin-top: 4px;
            display: none;
        }

        /* Autocomplete dropdown */
        .qp-autocomplete {
            position: relative;
        }

        .qp-autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            z-index: 100;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            margin-top: 2px;
        }

        .qp-autocomplete-list.open { display: block; }

        .qp-autocomplete-item {
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.1s;
        }

        .qp-autocomplete-item:last-child { border-bottom: none; }
        .qp-autocomplete-item:hover { background: var(--gray-50); }
        .qp-autocomplete-item.highlighted { background: #ede9fe; color: var(--primary); }

        /* Loading state in autocomplete */
        .qp-autocomplete-loading {
            padding: 12px 14px;
            font-size: 13px;
            color: var(--gray-400);
            text-align: center;
        }

        /* ======================================================
           DIVIDER
        ====================================================== */
        .qp-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }

        .qp-divider-line { flex: 1; height: 1px; background: var(--gray-200); }
        .qp-divider-text {
            font-size: 11px;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }

        /* ======================================================
           PAYMENT
        ====================================================== */
        #express-checkout-element { min-height: 50px; }

        .qp-payment-loading {
            background: var(--gray-100);
            border-radius: 8px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            font-size: 14px;
            gap: 10px;
            margin-bottom: 18px;
        }

        #payment-element { margin-bottom: 20px; }

        /* ======================================================
           PAY BUTTON
        ====================================================== */
        .qp-pay-btn {
            width: 100%;
            height: 54px;
            background: #0b1220;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease;
            letter-spacing: 0.2px;
            font-family: inherit;
        }

        .qp-pay-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(102,126,234,0.35);
        }

        .qp-pay-btn:active:not(:disabled) { transform: translateY(0); }

        .qp-pay-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* ======================================================
           ERROR BOX
        ====================================================== */
        .qp-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 16px;
            color: #dc2626;
            font-size: 14px;
            margin-top: 14px;
            display: none;
            line-height: 1.6;
        }

        /* ======================================================
           SPINNER
        ====================================================== */
        .qp-spin {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: white;
            border-radius: 50%;
            animation: qp-spin 0.7s linear infinite;
        }

        .qp-spin-dark {
            border-color: var(--gray-200);
            border-top-color: var(--primary);
        }

        .qp-spin-lg {
            width: 36px;
            height: 36px;
            border-width: 3px;
        }

        @keyframes qp-spin { to { transform: rotate(360deg); } }

        /* ======================================================
           ORDER SUMMARY
        ====================================================== */
        .qp-order-col { position: sticky; top: 80px; }

        .qp-order-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .qp-order-item:last-child { border-bottom: none; }

        .qp-item-img {
            width: 62px;
            height: 62px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            flex-shrink: 0;
        }

        .qp-item-ph {
            width: 62px;
            height: 62px;
            border-radius: 8px;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .qp-item-info { flex: 1; min-width: 0; }

        .qp-item-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
            line-height: 1.4;
        }

        .qp-item-variant {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 3px;
        }

        .qp-item-qty {
            display: inline-flex;
            align-items: center;
            background: var(--gray-100);
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-600);
            margin-top: 5px;
        }

        .qp-item-price {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-900);
            white-space: nowrap;
            align-self: flex-start;
            margin-top: 2px;
        }

        /* Totals */
        .qp-totals { margin-top: 16px; }

        .qp-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            font-size: 14px;
            color: var(--gray-500);
            border-bottom: 1px solid var(--gray-100);
        }

        .qp-total-row:last-child { border-bottom: none; }

        .qp-total-row.grand {
            border-top: 2px solid var(--gray-200);
            border-bottom: none;
            margin-top: 8px;
            padding-top: 16px;
            font-size: 18px;
            font-weight: 800;
            color: var(--gray-900);
        }

        /* Currency Banner */
        .qp-currency-bar {
            background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
            border-bottom: 1px solid rgba(102,126,234,0.15);
            padding: 10px 24px;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 13px;
            color: var(--gray-600);
            flex-wrap: wrap;
        }

        .qp-currency-bar.visible { display: flex; }

        .qp-currency-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            border: 1.5px solid var(--gray-200);
            border-radius: 20px;
            padding: 4px 14px;
            font-weight: 700;
            font-size: 13px;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.15s;
        }

        .qp-currency-pill:hover {
            border-color: var(--primary);
            background: #f5f3ff;
        }

        /* Conversion notice */
        .qp-conv-notice {
            background: #ede9fe;
            border: 1px solid #c4b5fd;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #6d28d9;
            margin-top: 12px;
            display: none;
            line-height: 1.7;
        }

        /* Badges */
        .qp-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-100);
        }

        .qp-badge {
            font-size: 11px;
            color: var(--gray-500);
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 20px;
            padding: 4px 10px;
            font-weight: 500;
        }

        /* ======================================================
           CURRENCY MODAL
        ====================================================== */
        .qp-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 20px;
        }

        .qp-modal-overlay.open { display: flex; }

        .qp-modal {
            background: white;
            border-radius: 16px;
            padding: 24px;
            max-width: 480px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .qp-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .qp-modal-title { font-size: 17px; font-weight: 700; }

        .qp-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-400);
            line-height: 1;
            padding: 0;
        }

        .qp-currency-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .qp-currency-opt {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.12s;
        }

        .qp-currency-opt:hover {
            border-color: var(--primary);
            background: #f5f3ff;
        }

        .qp-currency-opt.active {
            border-color: var(--primary);
            background: #ede9fe;
        }

        .qp-c-flag { font-size: 22px; }
        .qp-c-code { font-weight: 700; font-size: 14px; color: var(--gray-900); }
        .qp-c-name { font-size: 11px; color: var(--gray-400); margin-top: 1px; }

        /* ======================================================
           PAGE OVERLAY LOADER
        ====================================================== */
        .qp-page-loader {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.92);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 998;
            gap: 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-600);
        }

        .qp-page-loader .qp-spin {
            width: 40px;
            height: 40px;
            border-width: 3px;
            border-color: var(--gray-200);
            border-top-color: var(--primary);
        }

        /* ======================================================
           SECURITY NOTE
        ====================================================== */
        .qp-secure-note {
            text-align: center;
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        @yield('styles')
    </style>
</head>
<body>

<!-- Header -->
<header class="qp-header">
    <a href="/" class="qp-logo">EaszyPay</a>
    <div class="qp-secure">🔒 SSL Secure Checkout</div>
</header>

@yield('content')

@yield('scripts')

</body>
</html>