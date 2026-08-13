<?php
$s       = $session;
$items   = $s->items ?? [];
$sub     = (int) $s->subtotal;
$baseCur         = strtoupper($s->currency ?? 'USD');
$displayCur      = strtoupper($displayCurrency ?? $baseCur);
$displayRate     = (float) ($displayRate ?? 1.0);
$displaySub      = (int) ($displaySubtotal ?? $sub);
$shop            = $s->shop_domain ?? '';
$sid             = $s->session_id;
$skey            = $stripeKey ?? config('services.stripe.key');
$appUrl          = config('app.url');
$allCurrencies   = $currencies ?? [];

// Load store customization
$store = null;
if ($s->store_id) {
    $store = \App\Models\Store::find($s->store_id);
}

$primaryColor = $store?->checkout_primary_color ?? '#667eea';
$accentColor  = $store?->checkout_accent_color  ?? '#764ba2';
$logoUrl      = $store?->checkout_logo;
$shopName     = $store?->shop_name ?? 'Store';

$symbols = [
    'USD'=>'$','GBP'=>'£','EUR'=>'€','AUD'=>'A$','CAD'=>'CA$',
    'SGD'=>'S$','AED'=>'AED ','INR'=>'₹','JPY'=>'¥','NZD'=>'NZ$',
    'HKD'=>'HK$','CHF'=>'CHF ','SEK'=>'kr','NOK'=>'kr','DKK'=>'kr',
];
$sym = $symbols[$displayCur] ?? ($displayCur.' ');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — <?php echo htmlspecialchars($shopName); ?></title>
<script src="https://js.stripe.com/v3/"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg: #0a0a0a;
    --bg-card: #1a1a1a;
    --bg-input: #1f1f1f;
    --bg-hover: #2a2a2a;
    --border: #2a2a2a;
    --border-light: #333;
    --text: #ffffff;
    --text-muted: #9ca3af;
    --text-light: #6b7280;
    --primary: <?php echo $primaryColor; ?>;
    --accent: <?php echo $accentColor; ?>;
    --success: #10b981;
    --danger: #ef4444;
}

html { -webkit-font-smoothing: antialiased; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    line-height: 1.5;
}

/* HEADER */
.hdr {
    border-bottom: 1px solid var(--border);
    padding: 20px 24px;
}

.hdr-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 22px;
    font-weight: 700;
    color: var(--text);
}

.logo img { max-height: 36px; max-width: 180px; }

.hdr-secure {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--text-muted);
    font-size: 13px;
}

/* LAYOUT */
.layout {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 560px) minmax(0, 1fr);
    min-height: calc(100vh - 80px);
    justify-content: center;
}

@media (max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
}

.form-col {
    padding: 28px 32px 40px;
    border-right: 1px solid var(--border);
    max-width: 560px;
}

@media (max-width: 1100px) { .form-col { padding: 32px 40px; } }
@media (max-width: 900px) { .form-col { padding: 24px; border-right: none; } }

.order-col {
    padding: 28px 32px 40px;
    background: var(--bg);
}

@media (max-width: 1100px) { .order-col { padding: 32px 40px; } }
@media (max-width: 900px) { .order-col { padding: 24px; order: -1; border-bottom: 1px solid var(--border); } }

/* ============================================
   EXPRESS CHECKOUT BUTTONS (Apple Pay / Google Pay)
============================================ */
.express-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 24px;
}

@media (max-width: 480px) {
    .express-buttons { grid-template-columns: 1fr; }
}

.express-btn {
    height: 50px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 600;
    transition: opacity 0.2s;
    font-family: inherit;
}

.express-btn:hover { opacity: 0.9; }
.express-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-apple-pay {
    background: #000;
    color: #fff;
    border: 1px solid #fff;
}

.btn-apple-pay::before {
    content: '';
    display: inline-block;
    width: 20px;
    height: 24px;
    margin-right: 4px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01M12 6.5c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    vertical-align: middle;
}

.btn-google-pay {
    background: #fff;
    color: #000;
    font-weight: 500;
    border: 1px solid #fff;
}

.btn-google-pay::before {
    content: '';
    display: inline-block;
    width: 50px;
    height: 24px;
    margin-right: 4px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 24'%3E%3Cpath d='M14.5 12.27v-1.84h7.55c.08.5.13 1.02.13 1.55 0 1.9-.55 4.25-2.31 6-1.71 1.78-3.9 2.73-6.8 2.73C7.69 20.71 3 16.21 3 10.71S7.69.71 13.08.71c2.97 0 5.1 1.16 6.7 2.69l-1.88 1.88c-1.14-1.06-2.69-1.89-4.82-1.89C9.06 3.39 5.86 6.69 5.86 10.71s3.2 7.32 7.22 7.32c2.6 0 4.09-1.05 5.04-2 .77-.77 1.27-1.87 1.47-3.37l-6.09.01z' fill='%234285F4'/%3E%3Cpath d='M28.36 9.04c-3.55 0-6.45 2.7-6.45 6.43 0 3.71 2.9 6.43 6.45 6.43s6.45-2.72 6.45-6.43c0-3.73-2.9-6.43-6.45-6.43zm0 10.32c-1.95 0-3.62-1.61-3.62-3.89 0-2.31 1.67-3.89 3.62-3.89s3.62 1.59 3.62 3.89c0 2.28-1.68 3.89-3.62 3.89z' fill='%23EA4335'/%3E%3Cpath d='M42.34 9.04c-3.55 0-6.45 2.7-6.45 6.43 0 3.71 2.9 6.43 6.45 6.43s6.45-2.72 6.45-6.43c0-3.73-2.9-6.43-6.45-6.43zm0 10.32c-1.95 0-3.62-1.61-3.62-3.89 0-2.31 1.67-3.89 3.62-3.89s3.62 1.59 3.62 3.89c0 2.28-1.67 3.89-3.62 3.89z' fill='%23FBBC04'/%3E%3Cpath d='M55.95 9.42v1.04h-.1c-.63-.75-1.84-1.42-3.37-1.42-3.21 0-6.15 2.82-6.15 6.45 0 3.6 2.94 6.4 6.15 6.4 1.53 0 2.74-.68 3.37-1.45h.1v.92c0 2.46-1.32 3.78-3.44 3.78-1.73 0-2.81-1.24-3.25-2.29l-2.46 1.02c.71 1.71 2.58 3.81 5.71 3.81 3.32 0 6.13-1.95 6.13-6.71V9.42h-2.69zm-3.21 9.94c-1.95 0-3.59-1.64-3.59-3.88s1.64-3.92 3.59-3.92c1.93 0 3.44 1.66 3.44 3.92s-1.51 3.88-3.44 3.88z' fill='%2334A853'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
}

/* Hide express checkout element (we use custom buttons) */
#express-checkout-element { display: none; }

.or-divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 24px 0;
}

.or-divider::before,
.or-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-light);
}

.or-divider span {
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 500;
}

/* SECTIONS */
.section { margin-bottom: 32px; }

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
}

.section-sub {
    color: var(--primary);
    font-size: 14px;
    margin-bottom: 16px;
}

/* FORM */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

@media (max-width: 480px) {
    .form-row, .form-row-3 { grid-template-columns: 1fr; }
}

.form-group { margin-bottom: 12px; position: relative; }

.form-control {
    width: 100%;
    max-width: 100%;
    height: 46px;
    padding: 16px 12px 4px;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text);
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.15s;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 1px var(--primary);
}

.form-control:focus + .form-label,
.form-control.has-value + .form-label {
    transform: translateY(-10px) scale(0.8);
    color: var(--text-muted);
}

.form-control::placeholder { color: transparent; }

.form-label {
    position: absolute;
    left: 12px;
    top: 14px;
    color: var(--text-muted);
    font-size: 14px;
    pointer-events: none;
    transition: all 0.15s ease;
    transform-origin: left top;
}

.form-control.is-invalid { border-color: var(--danger); }

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%239ca3af'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    padding-right: 40px;
}

.form-select option {
    background: var(--bg-input);
    color: var(--text);
}

.checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    cursor: pointer;
}

.checkbox-wrap input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
    cursor: pointer;
}

.checkbox-wrap label {
    color: var(--text);
    font-size: 14px;
    cursor: pointer;
}

/* SHIPPING METHOD */
.shipping-box {
    background: var(--bg-input);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    color: var(--text-muted);
    font-size: 14px;
}

/* PAYMENT */
#payment-element {
    background: var(--bg-input);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 16px;
}

.pay-loading {
    background: var(--bg-input);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 60px;
    text-align: center;
    color: var(--text-muted);
    font-size: 14px;
}

.spin {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid var(--border-light);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: sp 0.7s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}

@keyframes sp { to { transform: rotate(360deg); } }

.pay-btn {
    width: 100%;
    height: 56px;
    background: var(--text);
    color: var(--bg);
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 24px;
    letter-spacing: 0.05em;
    transition: all 0.15s;
    text-transform: uppercase;
    font-family: inherit;
}

.pay-btn:hover:not(:disabled) { opacity: 0.9; }
.pay-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pay-btn .spin {
    border-color: rgba(0,0,0,0.2);
    border-top-color: var(--bg);
}

.error-box {
    background: rgba(239,68,68,0.1);
    border: 1px solid var(--danger);
    border-radius: 6px;
    padding: 12px 16px;
    color: var(--danger);
    font-size: 14px;
    margin-top: 14px;
    display: none;
}

/* ============================================
   ORDER SUMMARY
============================================ */
.order-summary { max-width: 480px; }

.order-item {
    display: flex;
    gap: 14px;
    margin-bottom: 24px;
    align-items: flex-start;
}

.item-img-wrap {
    position: relative;
    flex-shrink: 0;
}

.item-img {
    width: 64px;
    height: 64px;
    border-radius: 8px;
    object-fit: cover;
    background: var(--bg-input);
    border: 1px solid var(--border-light);
}

.item-qty-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: rgba(255,255,255,0.7);
    color: var(--bg);
    border-radius: 50%;
    min-width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.item-info { flex: 1; }
.item-name { color: var(--text); font-size: 14px; font-weight: 600; margin-bottom: 2px; }
.item-variant { color: var(--text-muted); font-size: 13px; }
.item-price { color: var(--text); font-size: 14px; font-weight: 600; white-space: nowrap; }

/* DISCOUNT */
.discount-row {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}

.discount-input {
    flex: 1;
    height: 44px;
    padding: 0 14px;
    border: 1.5px solid var(--border-light);
    border-radius: 6px;
    background: var(--bg-input);
    color: var(--text);
    font-size: 14px;
    outline: none;
    font-family: inherit;
}

.discount-input:focus { border-color: var(--primary); }

.discount-btn {
    padding: 0 22px;
    height: 44px;
    background: var(--bg-hover);
    color: var(--text);
    border: 1.5px solid var(--border-light);
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
}

.discount-btn:hover { border-color: var(--text-muted); }
.discount-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* TOTALS */
.totals { padding-top: 8px; }

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 14px;
    color: var(--text);
}

.total-row .label {
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 4px;
}

.total-row .help {
    color: var(--text-muted);
    cursor: help;
    font-size: 13px;
}

.total-row .value { color: var(--text); font-weight: 500; }
.total-row .value.muted { color: var(--text-muted); font-weight: 400; }

.total-divider {
    border-top: 1px solid var(--border-light);
    margin: 8px 0;
}

.total-row.grand {
    padding-top: 16px;
    font-size: 22px;
    font-weight: 700;
}

.total-row.grand .currency-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-right: 8px;
    font-weight: 500;
    vertical-align: middle;
}

.total-row.grand .value { font-weight: 700; }

/* LOADING OVERLAY */
.loader-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    gap: 14px;
    color: var(--text-muted);
    font-size: 14px;
}

.loader-overlay .spin {
    width: 36px;
    height: 36px;
    border-width: 3px;
    margin: 0;
}

.ship-rate {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border: 1.5px solid var(--border-light);
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    background: var(--bg-input);
}
.ship-rate.selected {
    border-color: var(--primary);
    box-shadow: 0 0 0 1px var(--primary);
}
.ship-rate input { accent-color: var(--primary); }
.policy-footer {
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    border-top: 1px solid var(--border);
    color: var(--text-muted);
    font-size: 13px;
}
.policy-footer a { color: var(--primary); text-decoration: none; }
.policy-footer a:hover { text-decoration: underline; }
</style>
</head>
<body>

<!-- Page Loader -->
<div class="loader-overlay" id="pgL">
    <div class="spin"></div>
    <span id="lmsg">Loading checkout...</span>
</div>

<!-- Header -->
<header class="hdr">
    <div class="hdr-inner">
        <?php if ($logoUrl): ?>
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($shopName); ?>" class="logo">
        <?php else: ?>
            <div class="logo"><?php echo htmlspecialchars($shopName); ?></div>
        <?php endif; ?>
        <div class="hdr-secure">🔒 Secure Checkout</div>
    </div>
</header>

<div class="layout">

    <!-- LEFT: FORM -->
    <div class="form-col">

        <!-- Express Checkout Buttons (Apple Pay / Google Pay) -->
        <div class="express-buttons" id="expressBtns">
            <button type="button" id="applePayBtn" class="express-btn btn-apple-pay" style="display:none;">Pay</button>
            <button type="button" id="googlePayBtn" class="express-btn btn-google-pay" style="display:none;">Pay</button>
        </div>

        <!-- Hidden Stripe Express Checkout Element (handles the actual payment) -->
        <div id="express-checkout-element"></div>

        <div class="or-divider" id="orDivider" style="display:none;"><span>OR</span></div>

        <!-- Contact -->
        <div class="section">
            <div class="section-head">
                <h2 class="section-title">Contact</h2>
            </div>

            <div class="form-group">
                <input type="email" id="email" class="form-control" placeholder="Email" autocomplete="email" oninput="floatLabel(this)">
                <label for="email" class="form-label">Email</label>
            </div>

            <div class="checkbox-wrap">
                <input type="checkbox" id="news" checked>
                <label for="news">Email me with news and offers</label>
            </div>
        </div>

        <!-- Delivery -->
        <div class="section">
            <div class="section-head">
                <h2 class="section-title">Delivery</h2>
            </div>

            <div class="form-group">
                <select id="country" class="form-control form-select has-value" onchange="onCountryChange()">
                    <option value="US">United States</option>
                    <option value="GB">United Kingdom</option>
                    <option value="CA">Canada</option>
                    <option value="AU">Australia</option>
                    <option value="DE">Germany</option>
                    <option value="FR">France</option>
                    <option value="IN" selected>India</option>
                    <option value="AE">UAE</option>
                    <option value="SG">Singapore</option>
                    <option value="JP">Japan</option>
                    <option value="NL">Netherlands</option>
                    <option value="SE">Sweden</option>
                    <option value="NZ">New Zealand</option>
                </select>
                <label class="form-label" style="transform:translateY(-10px) scale(0.8);">Country/Region</label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <input type="text" id="fn" class="form-control" placeholder="First name" autocomplete="given-name" oninput="floatLabel(this)">
                    <label for="fn" class="form-label">First name</label>
                </div>
                <div class="form-group">
                    <input type="text" id="ln" class="form-control" placeholder="Last name" autocomplete="family-name" oninput="floatLabel(this)">
                    <label for="ln" class="form-label">Last name</label>
                </div>
            </div>

            <div class="form-group">
                <input type="text" id="a1" class="form-control" placeholder="Address" autocomplete="address-line1" oninput="floatLabel(this)">
                <label for="a1" class="form-label">Address</label>
            </div>

            <div class="form-group">
                <input type="text" id="a2" class="form-control" placeholder="Apartment, suite, etc. (optional)" autocomplete="address-line2" oninput="floatLabel(this)">
                <label for="a2" class="form-label">Apartment, suite, etc. (optional)</label>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <input type="text" id="city" class="form-control" placeholder="City" autocomplete="address-level2" oninput="floatLabel(this)">
                    <label for="city" class="form-label">City</label>
                </div>
                <div class="form-group">
                    <select id="state" class="form-control form-select has-value">
                        <option value="">State</option>
                    </select>
                    <label class="form-label" style="transform:translateY(-10px) scale(0.8);">State</label>
                </div>
                <div class="form-group">
                    <input type="text" id="zip" class="form-control" placeholder="PIN code" autocomplete="postal-code" oninput="floatLabel(this)">
                    <label for="zip" class="form-label">PIN code</label>
                </div>
            </div>

            <div class="form-group">
                <input type="tel" id="phone" class="form-control" placeholder="Phone" autocomplete="tel" oninput="floatLabel(this)">
                <label for="phone" class="form-label">Phone</label>
            </div>

            <div class="checkbox-wrap">
                <input type="checkbox" id="save_info">
                <label for="save_info">Save this information for next time</label>
            </div>
        </div>

        <!-- Shipping method -->
        <div class="section">
            <div class="section-head">
                <h2 class="section-title">Shipping method</h2>
            </div>
            <div class="shipping-box" id="shippingBox">
                Enter your shipping address to view available shipping methods.
            </div>
            <div id="shippingRates" style="display:none;"></div>
        </div>

        <!-- Payment -->
        <div class="section">
            <div class="section-head">
                <h2 class="section-title">Payment</h2>
            </div>
            <p class="section-sub">All transactions are secure and encrypted.</p>

            <div class="pay-loading" id="payLoading">
                <span class="spin"></span> Loading payment form...
            </div>

            <div id="payment-element" style="display:none;"></div>

            <div class="error-box" id="errBox"></div>

            <button class="pay-btn" id="payBtn" disabled>
                <span class="spin" id="paySpn" style="display:none;"></span>
                <span id="payTxt">PAY NOW</span>
            </button>
        </div>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="order-col">
        <div class="order-summary">

            <!-- Items -->
            <?php foreach ($items as $item):
                $pr = (int)($item['price'] ?? $item['price_cents'] ?? 0);
                $qt = (int)($item['quantity'] ?? 1);
                $it = $pr * $qt;
                $vt = trim($item['variant_title'] ?? '');
            ?>
            <div class="order-item">
                <div class="item-img-wrap">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="item-img" onerror="this.style.display='none';">
                    <?php else: ?>
                        <div class="item-img" style="display:flex;align-items:center;justify-content:center;font-size:24px;">📦</div>
                    <?php endif; ?>
                    <div class="item-qty-badge"><?php echo $qt; ?></div>
                </div>
                <div class="item-info">
                    <div class="item-name"><?php echo htmlspecialchars($item['title'] ?? 'Product'); ?></div>
                    <?php if ($vt && strtolower($vt) !== 'default title'): ?>
                        <div class="item-variant"><?php echo htmlspecialchars($vt); ?></div>
                    <?php endif; ?>
                </div>
                <div class="item-price" data-base="<?php echo $it; ?>">
                    <?php echo $sym . number_format(($it * $displayRate) / 100, 2); ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Discount Code -->
            <div class="discount-row">
                <input type="text" id="discountCode" class="discount-input" placeholder="Discount code">
                <button type="button" class="discount-btn" id="applyDiscount" onclick="applyDiscountCode()">Apply</button>
            </div>

            <!-- Totals -->
            <div class="totals">
                <div class="total-row">
                    <span class="label">Subtotal</span>
                    <span class="value" id="subV"><?php echo $sym . number_format($displaySub/100, 2); ?></span>
                </div>
                <div class="total-row" id="discountRow" style="display:none;">
                    <span class="label">Discount <span id="discountCodeLabel"></span></span>
                    <span class="value" id="discountV" style="color:var(--success);">-<?php echo $sym; ?>0.00</span>
                </div>
                <div class="total-row">
                    <span class="label">Shipping <span class="help">ⓘ</span></span>
                    <span class="value muted" id="shipV">Enter shipping address</span>
                </div>

                <div class="total-divider"></div>

                <div class="total-row grand">
                    <span class="label">Total</span>
                    <span class="value">
                        <span class="currency-label" id="curLabel"><?php echo $displayCur; ?></span>
                        <span id="grandV"><?php echo $sym . number_format($displaySub/100, 2); ?></span>
                    </span>
                </div>


            </div>
        </div>
    </div>

</div>

<script>
var C = {
    sk:      '<?php echo $skey; ?>',
    url:     '<?php echo $appUrl; ?>',
    sid:     '<?php echo $sid; ?>',
    amt:     <?php echo $displaySub; ?>,
    cur:     '<?php echo strtolower($displayCur); ?>',
    baseCur: '<?php echo $baseCur; ?>',
    baseAmt: <?php echo $sub; ?>,
    baseSym: '<?php echo ($symbols[$baseCur] ?? $baseCur . ' '); ?>'
};

var CURS = <?php echo json_encode($allCurrencies); ?>;
var CC = {US:'USD',GB:'GBP',CA:'CAD',AU:'AUD',DE:'EUR',FR:'EUR',NL:'EUR',IT:'EUR',ES:'EUR',IE:'EUR',IN:'INR',AE:'AED',SG:'SGD',JP:'JPY',SE:'SEK',NO:'NOK',DK:'DKK',CH:'CHF',NZ:'NZD',ZA:'ZAR',BR:'BRL',MX:'MXN'};

var S = {
    stripe: null, elements: null, payEl: null, expEl: null,
    secret: null, piId: null,
    currency: C.cur, amount: C.amt, rate: <?php echo $displayRate; ?>,
    processing: false, ready: false,
    discount: 0, discountCode: '', discountAmount: 0, freeShipping: false,
    shippingCents: 0, shippingBase: 0, shippingTitle: 'Standard Shipping', shippingLoaded: false
};

var $ = function(id) { return document.getElementById(id); };

function floatLabel(input) {
    if (input.value) input.classList.add('has-value');
    else input.classList.remove('has-value');
}

function money(cents, cur) {
    var c = CURS[cur.toUpperCase()] || { symbol: cur + ' ' };
    var z = ['JPY','KRW'].indexOf(cur.toUpperCase()) >= 0;
    var a = z ? cents : cents / 100;
    return c.symbol + a.toLocaleString('en-US', {
        minimumFractionDigits: z ? 0 : 2,
        maximumFractionDigits: z ? 0 : 2
    });
}

// ============================================
// UPDATE PRICES - Local currency throughout
// ============================================
function updatePrices(cur, amt, rate) {
    S.currency = cur;
    S.amount = amt;
    S.rate = rate;

    // Calculate discount (percent or fixed amount converted by rate)
    var discountAmount = S.discountAmount > 0
        ? Math.round(S.discountAmount * rate)
        : Math.round(amt * S.discount / 100);
    var ship = S.freeShipping ? 0 : Math.round((S.shippingBase || 0) * rate);
    S.shippingCents = ship;
    var finalAmount = Math.max(0, amt - discountAmount + ship);

    // Update item prices (multiply base price by exchange rate)
    document.querySelectorAll('[data-base]').forEach(function(el) {
        var base = parseInt(el.dataset.base);
        var converted = Math.round(base * rate);
        el.textContent = money(converted, cur);
    });

    // Update subtotal
    $('subV').textContent = money(amt, cur);

    // Update discount
    if (S.discount > 0 || S.discountAmount > 0 || S.freeShipping) {
        $('discountRow').style.display = 'flex';
        $('discountCodeLabel').textContent = S.discountCode ? '(' + S.discountCode + ')' : '';
        $('discountV').textContent = S.freeShipping && discountAmount === 0 ? 'Free shipping' : ('-' + money(discountAmount, cur));
    } else {
        $('discountRow').style.display = 'none';
    }

    if (S.shippingLoaded && $('shipV')) {
        $('shipV').classList.remove('muted');
        $('shipV').textContent = (S.freeShipping || ship === 0) ? 'Free' : money(ship, cur);
    }

    // Update grand total
    $('grandV').textContent = money(finalAmount, cur);
    $('curLabel').textContent = cur.toUpperCase();

    // Update pay button
    $('payTxt').textContent = 'PAY ' + money(finalAmount, cur);

    S.finalAmount = finalAmount;
}

// ============================================
// APPLY DISCOUNT CODE (calls server to validate)
// ============================================
async function applyDiscountCode() {
    var code = $('discountCode').value.trim();
    if (!code) return;

    $('applyDiscount').disabled = true;
    $('applyDiscount').textContent = 'Checking...';

    try {
        var r = await fetch(C.url + '/api/validate-discount', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                code: code,
                shop_domain: '<?php echo $shop; ?>',
                session_id: C.sid
            })
        });

        var d = await r.json();

        if (d.valid) {
            S.discount = d.discount_percent || 0;
            S.discountAmount = d.discount_amount || 0;
            S.freeShipping = !!d.free_shipping;
            S.discountCode = code;
            updatePrices(S.currency, S.amount, S.rate);
            $('applyDiscount').textContent = '✓ Applied';
            $('applyDiscount').style.background = 'var(--success)';
            $('applyDiscount').style.color = '#fff';
            $('discountCode').disabled = true;
        } else {
            alert(d.error || 'Invalid discount code');
            $('applyDiscount').textContent = 'Apply';
            $('applyDiscount').disabled = false;
        }
    } catch(e) {
        alert('Could not validate discount code');
        $('applyDiscount').textContent = 'Apply';
        $('applyDiscount').disabled = false;
    }
}

// ============================================
// DETECT LOCATION
// ============================================
async function detectLocation() {
    try {
        $('lmsg').textContent = 'Detecting location...';
        var r = await fetch(C.url + '/api/detect-location');
        var d = await r.json();

        if (d.country_code) {
            var sel = $('country');
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === d.country_code) {
                    sel.selectedIndex = i;
                    await loadStates(d.country_code);
                    break;
                }
            }
        }

        return d.currency || C.baseCur;
    } catch(e) {
        return C.baseCur;
    }
}

async function getRate(from, to, amt) {
    if (from === to) return { to_currency: from, converted_amount: amt, rate: 1.0, exchange_rate: 1.0 };
    try {
        var r = await fetch(C.url + '/api/exchange-rate?from=' + from + '&to=' + to + '&amount=' + amt);
        return await r.json();
    } catch(e) {
        return { to_currency: from, converted_amount: amt, rate: 1.0, exchange_rate: 1.0, fallback: true };
    }
}

async function onCountryChange() {
    var country = $('country').value;
    if (!country) return;

    var newCurrency = CC[country];
    if (newCurrency && newCurrency !== S.currency.toUpperCase()) {
        await switchCurrency(newCurrency);
    }

    await loadStates(country);
    await loadShippingRates();
}

async function loadStates(country) {
    try {
        var r = await fetch(C.url + '/api/location/states?country=' + country);
        var d = await r.json();
        var sel = $('state');
        sel.innerHTML = '<option value="">State</option>';
        (d.states || []).forEach(function(state) {
            var opt = document.createElement('option');
            opt.value = state.code;
            opt.textContent = state.name;
            sel.appendChild(opt);
        });
    } catch(e) {}
}

async function switchCurrency(code) {
    $('pgL').style.display = 'flex';
    $('lmsg').textContent = 'Updating currency to ' + code + '...';

    try {
        var rateData = await getRate(C.baseCur, code, C.baseAmt);
        var cur = rateData.fallback ? C.baseCur : (rateData.to_currency || C.baseCur);
        var amt = rateData.converted_amount || C.baseAmt;
        var rate = rateData.rate ?? rateData.exchange_rate ?? 1.0;

        updatePrices(cur, amt, rate);

        var pi = await createPI(cur.toLowerCase(), S.finalAmount || amt);
        if (pi) {
            if (S.payEl) S.payEl.unmount();
            if (S.expEl) { try { S.expEl.unmount(); } catch(e){} }
            $('payLoading').style.display = 'block';
            $('payment-element').style.display = 'none';
            $('payBtn').disabled = true;
            S.ready = false;
            mountElements(pi.client_secret);
        }
    } catch(e) {}

    $('pgL').style.display = 'none';
}

async function createPI(cur, amt) {
    $('lmsg').textContent = 'Setting up payment...';
    try {
        var r = await fetch(C.url + '/api/payment-intent', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                session_id: C.sid,
                amount: amt,
                currency: cur,
                exchange_rate: S.rate,
                detected_currency: cur.toUpperCase(),
                shipping_amount: S.freeShipping ? 0 : Math.round((S.shippingBase || 0)),
                shipping_title: S.shippingTitle
            })
        });
        var d = await r.json();
        if (d.error) throw new Error(d.error.message);
        S.secret = d.client_secret;
        S.piId = d.payment_intent_id;
        if (d.amount) S.finalAmount = d.amount;
        if (d.currency) S.currency = d.currency;
        return d;
    } catch(e) {
        showError(e.message);
        return null;
    }
}

function mountElements(clientSecret) {
    S.stripe = Stripe(C.sk);

    S.elements = S.stripe.elements({
        clientSecret: clientSecret,
        appearance: {
            theme: 'night',
            variables: {
                colorPrimary: '<?php echo $primaryColor; ?>',
                colorBackground: '#1f1f1f',
                colorText: '#ffffff',
                colorDanger: '#ef4444',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                borderRadius: '6px',
                spacingUnit: '4px'
            }
        }
    });

    try {
        S.expEl = S.elements.create('expressCheckout', { buttonHeight: 50 });
        S.expEl.mount('#express-checkout-element');
        S.expEl.on('ready', function(ev) {
            var hasApple = ev.availablePaymentMethods && ev.availablePaymentMethods.applePay;
            var hasGoogle = ev.availablePaymentMethods && ev.availablePaymentMethods.googlePay;
            if (hasApple) { $('applePayBtn').style.display = 'flex'; $('orDivider').style.display = 'flex'; }
            if (hasGoogle) { $('googlePayBtn').style.display = 'flex'; $('orDivider').style.display = 'flex'; }
            if (!hasApple && !hasGoogle) {
                $('expressBtns').style.display = 'none';
                $('orDivider').style.display = 'none';
            }
        });
        S.expEl.on('confirm', async function() {
            if (S.processing) return;
            S.processing = true;
            var sr = await S.elements.submit();
            if (sr.error) { showError(sr.error.message); S.processing = false; return; }
            var res = await S.stripe.confirmPayment({
                elements: S.elements,
                clientSecret: S.secret,
                confirmParams: { return_url: C.url + '/checkout/success?session=' + C.sid }
            });
            if (res.error) { showError(res.error.message); S.processing = false; }
        });
    } catch(e) {}

    S.payEl = S.elements.create('payment', {
        layout: { type: 'accordion', defaultCollapsed: false, radios: true }
    });
    S.payEl.mount('#payment-element');
    S.payEl.on('ready', function() {
        $('payLoading').style.display = 'none';
        $('payment-element').style.display = 'block';
        $('payBtn').disabled = false;
        S.ready = true;
        $('payTxt').textContent = 'PAY ' + money(S.finalAmount || S.amount, S.currency);
    });
    S.payEl.on('change', function(ev) { if (ev.complete) hideError(); });
}

$('applePayBtn').addEventListener('click', function() {
    if (S.expEl) $('express-checkout-element').style.display = 'block';
});
$('googlePayBtn').addEventListener('click', function() {
    if (S.expEl) $('express-checkout-element').style.display = 'block';
});

async function loadPolicies() {
    try {
        var r = await fetch(C.url + '/api/checkout-policies?session_id=' + C.sid);
        var d = await r.json();
        var p = d.policies || {};
        if ($('polShipping') && p.shipping) $('polShipping').href = p.shipping;
        if ($('polRefund') && p.refund) $('polRefund').href = p.refund;
        if ($('polPrivacy') && p.privacy) $('polPrivacy').href = p.privacy;
        if ($('polTerms') && p.terms) $('polTerms').href = p.terms;
    } catch(e) {}
}

async function loadShippingRates() {
    var country = $('country') ? $('country').value : '';
    var state = $('state') ? $('state').value : '';
    if (!country) return;
    try {
        var r = await fetch(C.url + '/api/shipping-rates?session_id=' + encodeURIComponent(C.sid) + '&country=' + encodeURIComponent(country) + '&state=' + encodeURIComponent(state || ''));
        var d = await r.json();
        var rates = d.rates || [];
        if (!rates.length) return;
        if ($('shippingBox')) $('shippingBox').style.display = 'none';
        var wrap = $('shippingRates');
        if (!wrap) return;
        wrap.style.display = 'block';
        wrap.innerHTML = '';
        rates.forEach(function(rate, idx) {
            var displayPrice = Math.round((rate.price || 0) * (S.rate || 1));
            var row = document.createElement('label');
            row.className = 'ship-rate' + (idx === 0 ? ' selected' : '');
            row.innerHTML = '<span><input type="radio" name="shipRate" value="'+idx+'" '+(idx===0?'checked':'')+'> '+
                (rate.title || 'Shipping') + '</span><strong>' +
                (displayPrice === 0 ? 'Free' : money(displayPrice, S.currency)) + '</strong>';
            row.querySelector('input').addEventListener('change', function() {
                document.querySelectorAll('.ship-rate').forEach(function(el){ el.classList.remove('selected'); });
                row.classList.add('selected');
                S.shippingBase = rate.price || 0;
                S.shippingTitle = rate.title || 'Shipping';
                S.shippingLoaded = true;
                updatePrices(S.currency, S.amount, S.rate);
            });
            wrap.appendChild(row);
            if (idx === 0) {
                S.shippingBase = rate.price || 0;
                S.shippingTitle = rate.title || 'Shipping';
                S.shippingLoaded = true;
            }
        });
        updatePrices(S.currency, S.amount, S.rate);
        refreshPaymentForTotal();
    } catch(e) {}
}

async function refreshPaymentForTotal() {
    if (!S.currency || !S.finalAmount) return;
    var pi = await createPI(S.currency.toLowerCase(), S.finalAmount);
    if (!pi || !pi.client_secret) return;
    if (S.payEl) { try { S.payEl.unmount(); } catch(e) {} }
    if (S.expEl) { try { S.expEl.unmount(); } catch(e) {} }
    $('payLoading').style.display = 'block';
    $('payment-element').style.display = 'none';
    $('payBtn').disabled = true;
    S.ready = false;
    mountElements(pi.client_secret);
}

async function init() {
    $('pgL').style.display = 'flex';
    try {
        var detected = await detectLocation();
        var rateData = await getRate(C.baseCur, detected, C.baseAmt);
        var cur = rateData.fallback ? C.baseCur : (rateData.to_currency || C.baseCur);
        var amt = rateData.converted_amount || C.baseAmt;
        var rate = rateData.rate ?? rateData.exchange_rate ?? 1.0;
        updatePrices(cur, amt, rate);
        await loadShippingRates();
        var pi = await createPI(cur.toLowerCase(), S.finalAmount || amt);
        if (pi) mountElements(pi.client_secret);
        loadPolicies();
        ['city','zip','state','country'].forEach(function(id) {
            var el = $(id);
            if (el) el.addEventListener('change', function(){ loadShippingRates(); });
        });
        $('pgL').style.display = 'none';
    } catch(e) {
        $('pgL').style.display = 'none';
        showError(e.message || 'Could not load checkout');
    }
}

$('payBtn').addEventListener('click', async function() {
    if (S.processing || !S.ready) return;
    hideError();
    if (!validate()) return;
    S.processing = true;
    setLoading(true);
    try {
        var sr = await S.elements.submit();
        if (sr.error) { showError(sr.error.message); setLoading(false); S.processing = false; return; }
        var em = $('email').value.trim();
        var sh = getShipping();
        var finalAmt = S.finalAmount || S.amount;
        var pr = await fetch(C.url + '/api/payment-intent', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                session_id: C.sid,
                amount: finalAmt,
                currency: S.currency,
                exchange_rate: S.rate,
                detected_currency: S.currency.toUpperCase(),
                email: em,
                shipping: sh,
                shipping_amount: S.freeShipping ? 0 : Math.round(S.shippingBase || 0),
                shipping_title: S.shippingTitle
            })
        });
        var pd = await pr.json();
        if (pd.error) { showError(pd.error.message); setLoading(false); S.processing = false; return; }
        S.secret = pd.client_secret;
        var res = await S.stripe.confirmPayment({
            elements: S.elements,
            clientSecret: S.secret,
            confirmParams: {
                return_url: C.url + '/checkout/success?session=' + C.sid,
                payment_method_data: {
                    billing_details: {
                        name: sh.name,
                        email: em,
                        phone: $('phone').value.trim() || undefined,
                        address: sh.address
                    }
                }
            }
        });
        if (res.error) {
            showError(['card_error','validation_error'].indexOf(res.error.type) >= 0 ? res.error.message : 'Payment failed. Try another card.');
            setLoading(false);
            S.processing = false;
        }
    } catch(e) {
        showError('Unexpected error');
        setLoading(false);
        S.processing = false;
    }
});

function getShipping() {
    return {
        name: (($('fn').value || '') + ' ' + ($('ln').value || '')).trim(),
        address: {
            line1: $('a1').value.trim() || '',
            line2: $('a2').value.trim() || null,
            city: $('city').value.trim() || '',
            state: $('state').value || null,
            postal_code: $('zip').value.trim() || '',
            country: $('country').value || 'US'
        }
    };
}

function validate() {
    var f = [
        {id:'email', ck: function(v){return v && v.indexOf('@') > 0;}, m: 'Valid email required'},
        {id:'fn', ck: function(v){return v.length > 0;}, m: 'First name required'},
        {id:'ln', ck: function(v){return v.length > 0;}, m: 'Last name required'},
        {id:'a1', ck: function(v){return v.length > 0;}, m: 'Address required'},
        {id:'city', ck: function(v){return v.length > 0;}, m: 'City required'},
        {id:'zip', ck: function(v){return v.length > 0;}, m: 'PIN code required'}
    ];
    f.forEach(function(x) { var e = $(x.id); if (e) e.classList.remove('is-invalid'); });
    for (var i = 0; i < f.length; i++) {
        var x = f[i], e = $(x.id), v = e ? e.value.trim() : '';
        if (!x.ck(v)) {
            if (e) { e.classList.add('is-invalid'); e.scrollIntoView({behavior:'smooth', block:'center'}); e.focus(); }
            showError(x.m);
            return false;
        }
    }
    return true;
}

function setLoading(on) {
    $('payBtn').disabled = on;
    $('paySpn').style.display = on ? 'inline-block' : 'none';
    $('payTxt').textContent = on ? 'PROCESSING...' : ('PAY ' + money(S.finalAmount || S.amount, S.currency));
}
function showError(msg) {
    $('errBox').textContent = msg;
    $('errBox').style.display = 'block';
    $('errBox').scrollIntoView({behavior:'smooth', block:'nearest'});
}
function hideError() { $('errBox').style.display = 'none'; }
function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function(ch) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]);
    });
}

init();
</script>

<footer class="policy-footer" id="policyFooter">
    <a id="polShipping" href="#" target="_blank" rel="noopener">Shipping policy</a>
    <a id="polRefund" href="#" target="_blank" rel="noopener">Refund policy</a>
    <a id="polPrivacy" href="#" target="_blank" rel="noopener">Privacy policy</a>
    <a id="polTerms" href="#" target="_blank" rel="noopener">Terms of service</a>
</footer>
</body>
</html>
