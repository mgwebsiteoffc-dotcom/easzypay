<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EaszyPay — Shopify always checks out in the store currency. Currency switchers only change the storefront. Charge the shopper’s local money (AED, EUR, GBP…) even when Shopify Payments multi-currency is not available for your shop.">
    <meta name="keywords" content="Shopify checkout store currency, currency switcher checkout, Shopify AED checkout, Shopify Payments India, Shop Pay local currency, EaszyPay">
    <meta property="og:title" content="EaszyPay — Charge the currency they browsed">
    <meta property="og:description" content="Shopify checkout is always store currency. A Dubai shopper can see AED on the site and USD at pay. EaszyPay takes the local currency through Stripe.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <title>EaszyPay — Local-currency pay when Shopify checkout is locked to the store</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink: #0b1220;
            --muted: #5b6578;
            --line: #e6e9f0;
            --paper: #f6f4ef;
            --white: #ffffff;
            --brand: #0f766e;
            --brand-2: #134e4a;
        }
        html { scroll-behavior: smooth; -webkit-font-smoothing: antialiased; }
        body {
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            color: var(--ink);
            background: var(--paper);
        }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 0 24px; }
        .sans { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(246,244,239,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .logo { font-weight: 800; font-size: 22px; letter-spacing: -0.03em; }
        .logo span { color: var(--brand); }
        .nav-links { display: flex; gap: 28px; list-style: none; font-size: 14px; }
        .nav-links a { color: var(--muted); }
        .nav-links a:hover { color: var(--ink); }
        .nav-cta { display: flex; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 11px 18px; border-radius: 999px; font-size: 14px; font-weight: 650;
            font-family: inherit;
        }
        .btn-ghost { border: 1px solid var(--line); background: var(--white); }
        .btn-solid { background: var(--ink); color: #fff; }
        .btn-solid:hover { background: var(--brand-2); }

        .hero { padding: 72px 0 48px; }
        .hero-grid { display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 48px; align-items: center; }
        .eyebrow {
            display: inline-block; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--brand-2); font-weight: 700; margin-bottom: 16px;
        }
        h1 { font-size: clamp(2.05rem, 4.5vw, 3.45rem); line-height: 1.08; letter-spacing: -0.035em; margin-bottom: 20px; }
        .lede { font-size: 1.12rem; line-height: 1.7; color: var(--muted); max-width: 40rem; margin-bottom: 28px; }
        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .fine { font-size: 13px; color: var(--muted); }

        .funnel {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(15,23,42,0.06);
        }
        .funnel header { padding: 16px 20px; border-bottom: 1px solid var(--line); font-size: 13px; font-weight: 700; }
        .row { display: grid; grid-template-columns: 132px 1fr 88px; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--line); align-items: center; font-size: 13px; }
        .row:last-child { border-bottom: 0; }
        .ok { color: #0f766e; font-weight: 700; }
        .fail { color: #b42318; font-weight: 700; }
        .muted { color: var(--muted); }

        .problem { padding: 28px 0 80px; }
        .problem-box { background: var(--ink); color: #f4efe6; border-radius: 28px; padding: 48px; }
        .problem-box h2 { font-size: clamp(1.7rem, 3vw, 2.4rem); margin: 10px 0 16px; }
        .problem-box p { max-width: 50rem; line-height: 1.75; color: rgba(244,239,230,0.82); }
        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 28px; }
        .pane { border-radius: 16px; padding: 18px; }
        .pane.bad { background: #3a1d1c; }
        .pane.good { background: #16332f; }
        .pane h3 { font-size: 15px; margin-bottom: 8px; }
        .pane p { font-size: 14px; color: rgba(244,239,230,0.78); }

        section { padding: 80px 0; }
        .center { text-align: center; }
        h2 { font-size: clamp(1.8rem, 3vw, 2.6rem); letter-spacing: -0.03em; margin-bottom: 12px; }
        .sub { color: var(--muted); max-width: 700px; margin: 0 auto 40px; line-height: 1.7; }

        .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .step { background: var(--white); border: 1px solid var(--line); border-radius: 18px; padding: 22px; }
        .num { width: 32px; height: 32px; border-radius: 50%; background: var(--ink); color: #fff; display: grid; place-items: center; font-size: 13px; margin-bottom: 14px; }
        .step h3 { font-size: 17px; margin-bottom: 8px; }
        .step p { font-size: 14px; color: var(--muted); line-height: 1.6; }

        .grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .feat { background: var(--white); border: 1px solid var(--line); border-radius: 18px; padding: 22px; }
        .feat h3 { font-size: 17px; margin: 8px 0; }
        .feat p { font-size: 14px; color: var(--muted); line-height: 1.65; }

        .price-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .price { background: var(--white); border: 1px solid var(--line); border-radius: 22px; padding: 28px; }
        .price.hi { background: var(--ink); color: #fff; border-color: var(--ink); }
        .price .name { font-size: 13px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--brand); margin-bottom: 10px; }
        .price.hi .name { color: #99f6e4; }
        .price .amt { font-size: 40px; font-weight: 800; }
        .price ul { list-style: none; margin: 18px 0 24px; }
        .price li { padding: 7px 0; font-size: 14px; border-bottom: 1px solid var(--line); }
        .price.hi li { border-color: rgba(255,255,255,0.1); }
        .price .btn { width: 100%; }

        .faq { max-width: 760px; margin: 0 auto; }
        .faq details { background: var(--white); border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; margin-bottom: 10px; }
        .faq summary { cursor: pointer; font-weight: 650; }
        .faq p { margin-top: 10px; color: var(--muted); line-height: 1.65; font-size: 15px; }

        .cta { background: var(--brand-2); color: #fff; border-radius: 28px; padding: 56px 32px; text-align: center; }
        .cta p { color: rgba(255,255,255,0.78); margin: 12px auto 24px; max-width: 580px; }

        footer { padding: 48px 0 28px; border-top: 1px solid var(--line); color: var(--muted); font-size: 14px; }
        .foot { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }

        @media (max-width: 900px) {
            .nav-links { display: none; }
            .hero-grid, .split, .steps, .grid3, .price-grid { grid-template-columns: 1fr; }
            .row { grid-template-columns: 1fr; gap: 4px; }
            .problem-box { padding: 28px; }
            .hero { padding-top: 40px; }
        }
    </style>
</head>
<body>
<nav class="nav sans">
    <div class="wrap nav-inner">
        <a class="logo" href="/">Easzy<span>Pay</span></a>
        <ul class="nav-links">
            <li><a href="#problem">The rule</a></li>
            <li><a href="#how">How it works</a></li>
            <li><a href="#features">Product</a></li>
            <li><a href="#pricing">Pricing</a></li>
            <li><a href="#faq">FAQ</a></li>
        </ul>
        <div class="nav-cta">
            <a class="btn btn-ghost" href="{{ route('tenant.login') }}">Sign in</a>
            <a class="btn btn-solid" href="{{ route('tenant.register') }}">Start free</a>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="wrap hero-grid">
        <div>
            <div class="eyebrow sans">For Shopify stores selling across borders</div>
            <h1>Shopify always checks out in the store currency. The switcher is only a label.</h1>
            <p class="lede">Store in USD, INR, GBP — it does not matter where the buyer lives. Product page and cart can show AED, EUR, SAR via a currency switcher. The moment they start checkout, Shopify charges the <em>store</em> currency. They did not change their mind. The money on the pay page did.</p>
            <div class="hero-actions sans">
                <a class="btn btn-solid" href="{{ route('tenant.register') }}">Take payment in their currency</a>
                <a class="btn btn-ghost" href="#problem">Why Shopify does this</a>
            </div>
            <p class="fine sans">Shop Pay / Shopify Payments can present local currency in some markets. That path is not available for India-based shops.</p>
        </div>
        <div class="funnel sans">
            <header>Example — store currency USD, visitor in Dubai</header>
            <div class="row"><span class="muted">Browse</span><span>Switcher shows AED 695</span><span class="ok">Local</span></div>
            <div class="row"><span class="muted">Product</span><span>Still AED 695</span><span class="ok">Local</span></div>
            <div class="row"><span class="muted">Cart</span><span>Still AED 695</span><span class="ok">Local</span></div>
            <div class="row"><span class="muted">Shopify checkout</span><span>Pay page: $189.00 USD</span><span class="fail">Store $</span></div>
            <div class="row"><span class="muted">EaszyPay</span><span>They pay AED 695 on Stripe</span><span class="ok">Local</span></div>
        </div>
    </div>
</header>

<div class="problem" id="problem">
    <div class="wrap">
        <div class="problem-box">
            <div class="eyebrow sans" style="color:#99f6e4;">How Shopify actually works</div>
            <h2>Checkout is store currency. Full stop.</h2>
            <p>Shopify processes payment in the shop’s currency. A currency converter, Geolocation app, or theme snippet only rewrites prices on the storefront. It cannot make native checkout accept AED, EUR, or any other nation’s money unless Shopify Payments / Shop Pay multi-currency is on for that shop and that market.</p>
            <p style="margin-top:14px;">For many merchants — including almost every India-registered store — that Shopify Payments path is not enabled. So a Dubai customer who added an AED price still faces USD (or INR, or GBP) at pay. Add-to-cart looks healthy. Checkout starts. Payment does not finish.</p>
            <p style="margin-top:14px;">EaszyPay is the pay step that is allowed to charge the browsed currency. Stripe collects AED / EUR / GBP / INR. Shopify still gets a paid order you fulfill as usual.</p>
            <div class="split sans">
                <div class="pane bad">
                    <h3>Native checkout</h3>
                    <p>Always store currency. Switcher dies at the pay page. Shop Pay local currency only where Shopify Payments supports that market — not India.</p>
                </div>
                <div class="pane good">
                    <h3>EaszyPay checkout</h3>
                    <p>Detect country, convert from the catalog, charge that local currency on Stripe. Coupons, shipping, thank-you stay in the flow.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<section id="how">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">How it works</div>
            <h2>Keep Shopify. Change only what they pay in.</h2>
            <p class="sub">Catalog, inventory, and fulfillment stay on Shopify. Buy Now opens a checkout that does not snap back to store currency.</p>
        </div>
        <div class="steps sans">
            <div class="step"><div class="num">1</div><h3>Connect the shop</h3><p>Install on .myshopify.com. We add a Buy Now path next to Add to cart.</p></div>
            <div class="step"><div class="num">2</div><h3>Charge their money</h3><p>Country in, live FX from your store currency, Stripe total in AED, EUR, USD, INR — whatever they browsed.</p></div>
            <div class="step"><div class="num">3</div><h3>Same merchandising</h3><p>Discount codes, delivery profiles, duties, and policies load from Shopify.</p></div>
            <div class="step"><div class="num">4</div><h3>Paid order in admin</h3><p>Stripe succeeds → Shopify order is created paid. Thank-you stays on EaszyPay.</p></div>
        </div>
    </div>
</section>

<section id="features" style="padding-top:0;">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">Product</div>
            <h2>A real charge in local currency — not another switcher.</h2>
        </div>
        <div class="grid3 sans">
            <div class="feat"><h3>Any store currency</h3><p>USD, INR, GBP, EUR… checkout on Shopify is still that. EaszyPay is the layer that can collect the shopper’s.</p></div>
            <div class="feat"><h3>Any buyer market</h3><p>Gulf, EU, UK, US, India. The pay page matches what the switcher already showed.</p></div>
            <div class="feat"><h3>Shopify coupons</h3><p>Live Discount API — same percentage or amount native checkout would apply.</p></div>
            <div class="feat"><h3>Your shipping &amp; duties</h3><p>Delivery profiles and zones, plus duties when Shopify returns them.</p></div>
            <div class="feat"><h3>Apple Pay &amp; Google Pay</h3><p>Stripe Express Checkout, device-aware.</p></div>
            <div class="feat"><h3>Thank-you that stays</h3><p>Confirmation on EaszyPay. No bounce to a Shopify status URL.</p></div>
        </div>
    </div>
</section>

<section id="pricing">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">Pricing</div>
            <h2>Priced for recovered checkouts, not a fake currency badge.</h2>
        </div>
        <div class="price-grid sans">
            <div class="price">
                <div class="name">Starter</div>
                <div class="amt">$29</div>
                <div class="fine">/month + 1.5% per order</div>
                <ul>
                    <li>1 Shopify store</li>
                    <li>Local-currency Stripe checkout</li>
                    <li>Cards &amp; wallets</li>
                    <li>Order sync</li>
                </ul>
                <a class="btn btn-ghost" href="{{ route('tenant.register') }}">Start trial</a>
            </div>
            <div class="price hi">
                <div class="name">Growth</div>
                <div class="amt">$79</div>
                <div class="fine">/month + 1% per order</div>
                <ul>
                    <li>Up to 5 stores</li>
                    <li>Coupons &amp; shipping profiles</li>
                    <li>Priority support</li>
                    <li>Analytics</li>
                </ul>
                <a class="btn btn-solid" href="{{ route('tenant.register') }}" style="background:#fff;color:#0b1220;">Start trial</a>
            </div>
            <div class="price">
                <div class="name">Enterprise</div>
                <div class="amt">$199</div>
                <div class="fine">/month + 0.5% per order</div>
                <ul>
                    <li>Unlimited stores</li>
                    <li>Dedicated support</li>
                    <li>Custom branding</li>
                    <li>SLA</li>
                </ul>
                <a class="btn btn-ghost" href="{{ route('tenant.register') }}">Talk to us</a>
            </div>
        </div>
    </div>
</section>

<section id="faq">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">FAQ</div>
            <h2>Straight answers</h2>
        </div>
        <div class="faq">
            <details open>
                <summary>Does Shopify ever checkout in the shopper’s currency?</summary>
                <p>Only when Shopify Payments / Shop Pay multi-currency is enabled for that shop and that market. Otherwise checkout is always the store currency — USD shop → USD pay, INR shop → INR pay — no matter if the visitor is in Dubai, London, or Delhi.</p>
            </details>
            <details>
                <summary>Why doesn’t a currency switcher fix this?</summary>
                <p>It only changes numbers on the theme (product, collection, cart). Native checkout does not read that label. The Dubai example: AED on the site, USD on the Shopify pay page.</p>
            </details>
            <details>
                <summary>Is this only for Indian merchants?</summary>
                <p>India is the sharpest case because Shopify Payments (and therefore Shop Pay local currency) is not offered there. The same leak exists for any shop that cannot turn on Markets presentment at checkout.</p>
            </details>
            <details>
                <summary>Do orders still land in Shopify?</summary>
                <p>Yes. After Stripe succeeds we create a paid order with line items, discounts, shipping, and address.</p>
            </details>
        </div>
    </div>
</section>

<div class="wrap" style="padding-bottom:80px;">
    <div class="cta">
        <h2>They already agreed to the AED price. Don’t make them pay USD.</h2>
        <p>If checkout starts and payment dies, check whether the pay page flipped back to store currency. That is the job EaszyPay does.</p>
        <a class="btn btn-solid sans" href="{{ route('tenant.register') }}" style="background:#fff;color:#134e4a;">Create your EaszyPay account</a>
    </div>
</div>

<footer class="sans">
    <div class="wrap foot">
        <div><strong style="color:var(--ink);">EaszyPay</strong> · Local currency at pay, not only on the theme</div>
        <div>© {{ date('Y') }} EaszyPay. Not affiliated with Shopify Inc.</div>
    </div>
</footer>
</body>
</html>
