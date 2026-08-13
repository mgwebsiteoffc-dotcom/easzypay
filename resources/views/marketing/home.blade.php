<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EaszyPay — Shopify checkout in the shopper’s local currency. Currency switchers change the storefront. Native checkout snaps back to your store currency. That is why carts start and never finish.">
    <meta name="keywords" content="Shopify checkout currency, Shopify INR checkout, currency switcher checkout, abandoned checkout Shopify, EaszyPay, international Shopify">
    <meta property="og:title" content="EaszyPay — Checkout in the currency they browsed">
    <meta property="og:description" content="If your Shopify store is billed in INR, shoppers see USD on the site and INR at checkout. EaszyPay keeps the charge in their local currency.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <title>EaszyPay — Stop losing international checkouts to store currency</title>
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
        h1 { font-size: clamp(2.1rem, 4.6vw, 3.6rem); line-height: 1.08; letter-spacing: -0.035em; margin-bottom: 20px; }
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
        .row { display: grid; grid-template-columns: 140px 1fr 90px; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--line); align-items: center; font-size: 13px; }
        .row:last-child { border-bottom: 0; }
        .ok { color: #0f766e; font-weight: 700; }
        .fail { color: #b42318; font-weight: 700; }
        .muted { color: var(--muted); }

        .problem { padding: 28px 0 80px; }
        .problem-box { background: var(--ink); color: #f4efe6; border-radius: 28px; padding: 48px; }
        .problem-box h2 { font-size: clamp(1.7rem, 3vw, 2.4rem); margin: 10px 0 16px; }
        .problem-box p { max-width: 48rem; line-height: 1.75; color: rgba(244,239,230,0.82); }
        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 28px; }
        .pane { border-radius: 16px; padding: 18px; }
        .pane.bad { background: #3a1d1c; }
        .pane.good { background: #16332f; }
        .pane h3 { font-size: 15px; margin-bottom: 8px; }
        .pane p { font-size: 14px; color: rgba(244,239,230,0.78); }

        section { padding: 80px 0; }
        .center { text-align: center; }
        h2 { font-size: clamp(1.8rem, 3vw, 2.6rem); letter-spacing: -0.03em; margin-bottom: 12px; }
        .sub { color: var(--muted); max-width: 680px; margin: 0 auto 40px; line-height: 1.7; }

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
        .cta p { color: rgba(255,255,255,0.78); margin: 12px auto 24px; max-width: 560px; }

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
            <li><a href="#problem">Why carts die</a></li>
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
            <div class="eyebrow sans">For Shopify owners selling internationally</div>
            <h1>They add to cart in dollars. Checkout slaps them with rupees. They leave.</h1>
            <p class="lede">Your store currency is INR. A currency switcher (or Markets) shows the product page in USD, EUR, GBP. Shopify’s own checkout ignores that and charges the store currency. Shoppers think the price changed. Checkout starts. Payment never finishes.</p>
            <div class="hero-actions sans">
                <a class="btn btn-solid" href="{{ route('tenant.register') }}">Keep checkout in their currency</a>
                <a class="btn btn-ghost" href="#problem">See the drop-off</a>
            </div>
            <p class="fine sans">EaszyPay is a Buy Now checkout that actually charges local currency — not a storefront label.</p>
        </div>
        <div class="funnel sans">
            <header>What analytics usually show</header>
            <div class="row"><span class="muted">Product page</span><span>US visitor sees $189</span><span class="ok">Browse</span></div>
            <div class="row"><span class="muted">Add to cart</span><span>Cart still says $189</span><span class="ok">Intent</span></div>
            <div class="row"><span class="muted">Checkout started</span><span>Shopify checkout: ₹16,420 INR</span><span class="fail">Shock</span></div>
            <div class="row"><span class="muted">Payment</span><span>Card / wallet abandoned</span><span class="fail">Lost</span></div>
            <div class="row"><span class="muted">EaszyPay</span><span>Checkout stays $189 (or their local money)</span><span class="ok">Paid</span></div>
        </div>
    </div>
</header>

<div class="problem" id="problem">
    <div class="wrap">
        <div class="problem-box">
            <div class="eyebrow sans" style="color:#99f6e4;">The real leak</div>
            <h2>Currency apps change the storefront. They do not change Shopify checkout.</h2>
            <p>If the shop is set to INR (common for Indian merchants shipping worldwide), every native checkout is INR — even when the theme, Geolocation app, or a currency converter advertised dollars. The customer already decided at $189. At pay they see a five-digit rupee total they cannot map to their card statement. That is not a traffic problem. It is a checkout-currency problem.</p>
            <p style="margin-top:14px;">EaszyPay replaces only the pay step. The shopper finishes in the currency they browsed. Stripe collects that amount. Shopify still gets a paid order you can fulfill.</p>
            <div class="split sans">
                <div class="pane bad">
                    <h3>Native Shopify checkout</h3>
                    <p>Locked to store currency. INR shop → INR pay page. Currency switcher stops at the cart. High “checkout initiated, not completed.”</p>
                </div>
                <div class="pane good">
                    <h3>EaszyPay checkout</h3>
                    <p>Detect country, convert from your catalog, charge USD / EUR / GBP / AED (or keep INR for India). Same coupons, shipping, thank-you.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<section id="how">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">How it works</div>
            <h2>Same catalog. Checkout that matches the price they clicked.</h2>
            <p class="sub">You keep Shopify for products, inventory, and fulfillment. We take over the moment they tap Buy Now so the currency does not flip.</p>
        </div>
        <div class="steps sans">
            <div class="step"><div class="num">1</div><h3>Connect the store</h3><p>Install EaszyPay on your .myshopify.com shop. We add a Buy Now path beside Add to cart.</p></div>
            <div class="step"><div class="num">2</div><h3>Detect the market</h3><p>Country from the shopper. Convert from your store currency with a live rate. Show that money through pay.</p></div>
            <div class="step"><div class="num">3</div><h3>Shopify rules still run</h3><p>Discount codes, delivery profiles, duties, and policies load the same way they would on Shopify checkout.</p></div>
            <div class="step"><div class="num">4</div><h3>Order lands paid</h3><p>Stripe succeeds → we create the Shopify order. Thank-you stays on EaszyPay so they are not bounced to a status URL.</p></div>
        </div>
    </div>
</section>

<section id="features" style="padding-top:0;">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">Product</div>
            <h2>Built for international demand on an INR (or any) store.</h2>
        </div>
        <div class="grid3 sans">
            <div class="feat"><h3>Checkout currency ≠ store currency</h3><p>That is the whole product. Display and charge the shopper’s money. Your admin still records the shop currency.</p></div>
            <div class="feat"><h3>Shopify coupons</h3><p>Codes like FESTIVE100 validate live against Shopify’s Discount API — same math as native checkout.</p></div>
            <div class="feat"><h3>Your shipping &amp; duties</h3><p>Delivery profiles and zones (US $30, rest of world, India) plus duties when Shopify applies them.</p></div>
            <div class="feat"><h3>Apple Pay &amp; Google Pay</h3><p>Wallets on Stripe Express Checkout, shown only on the right device.</p></div>
            <div class="feat"><h3>Address fill</h3><p>Street suggestions fill city, state, and PIN so rates can calculate before they pay.</p></div>
            <div class="feat"><h3>Thank-you that stays</h3><p>Confirmation stays on EaszyPay. No auto-redirect that drops the customer.</p></div>
        </div>
    </div>
</section>

<section id="pricing">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">Pricing</div>
            <h2>Pay for recovered international checkouts, not another currency widget.</h2>
        </div>
        <div class="price-grid sans">
            <div class="price">
                <div class="name">Starter</div>
                <div class="amt">$29</div>
                <div class="fine">/month + 1.5% per order</div>
                <ul>
                    <li>1 Shopify store</li>
                    <li>Local-currency checkout</li>
                    <li>Stripe cards &amp; wallets</li>
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
            <h2>The questions owners actually ask</h2>
        </div>
        <div class="faq">
            <details open>
                <summary>Why does Shopify checkout stay in INR if my site shows USD?</summary>
                <p>Shopify checkout uses the shop’s store currency (and Markets presentment only when that market is fully enabled). Theme converters and most currency apps only rewrite prices on the storefront. At checkout Shopify goes back to INR. That mismatch is why sessions show “checkout started” and no sale.</p>
            </details>
            <details>
                <summary>We sell to the US, UK, Gulf — not only India. Does this still apply?</summary>
                <p>Yes. Any time store currency ≠ shopper currency, native checkout creates sticker shock. EaszyPay charges the local currency for that country, then writes the paid order to Shopify.</p>
            </details>
            <details>
                <summary>Will orders still appear in Shopify?</summary>
                <p>Yes. After Stripe succeeds we create a paid order with line items, discounts, shipping, and the customer address, tagged EaszyPay.</p>
            </details>
            <details>
                <summary>Do coupons and shipping still match Shopify?</summary>
                <p>Discount codes hit Shopify’s Discount API. Shipping comes from your delivery profiles and zones. Duties are included when Shopify returns them.</p>
            </details>
        </div>
    </div>
</section>

<div class="wrap" style="padding-bottom:80px;">
    <div class="cta">
        <h2>Stop paying for traffic that dies the second they see INR.</h2>
        <p>If add-to-cart is healthy and checkout completion is not, it is probably the currency flip — not your product.</p>
        <a class="btn btn-solid sans" href="{{ route('tenant.register') }}" style="background:#fff;color:#134e4a;">Create your EaszyPay account</a>
    </div>
</div>

<footer class="sans">
    <div class="wrap foot">
        <div><strong style="color:var(--ink);">EaszyPay</strong> · Checkout in the currency they browsed</div>
        <div>© {{ date('Y') }} EaszyPay. Not affiliated with Shopify Inc.</div>
    </div>
</footer>
</body>
</html>
