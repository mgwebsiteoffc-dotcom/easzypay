<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EaszyPay — Let Indian customers pay Shopify stores in INR. Shopify Payments is not available in India. We add a Buy Now checkout with Stripe, coupons, shipping, and a Shopify thank-you page.">
    <meta name="keywords" content="Shopify India INR, Shopify local currency India, Stripe Shopify India, Shopify Payments India, EaszyPay">
    <meta property="og:title" content="EaszyPay — INR checkout for Shopify stores">
    <meta property="og:description" content="Shopify does not offer Shopify Payments in India. EaszyPay lets shoppers pay in rupees with Stripe, then syncs the paid order back to Shopify.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <title>EaszyPay — Local currency checkout for Shopify (India)</title>
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
            --gold: #c4a35a;
            --danger: #b42318;
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
        .hero-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 56px; align-items: center; }
        .eyebrow {
            display: inline-block; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--brand-2); font-weight: 700; margin-bottom: 16px;
        }
        h1 { font-size: clamp(2.2rem, 5vw, 4rem); line-height: 1.08; letter-spacing: -0.035em; margin-bottom: 20px; }
        .lede { font-size: 1.15rem; line-height: 1.7; color: var(--muted); max-width: 38rem; margin-bottom: 28px; }
        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .fine { font-size: 13px; color: var(--muted); }

        .card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 20px 50px rgba(15,23,42,0.06);
        }
        .compare { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .compare article { padding: 16px; border-radius: 14px; }
        .bad { background: #fdecea; border: 1px solid #f5c2c0; }
        .good { background: #e7f6f3; border: 1px solid #b7e0d8; }
        .compare h3 { font-size: 15px; margin-bottom: 8px; }
        .compare p { font-size: 13px; line-height: 1.55; color: #3f4a5a; }

        .problem { padding: 28px 0 80px; }
        .problem-box {
            background: var(--ink); color: #f4efe6; border-radius: 28px; padding: 48px;
        }
        .problem-box h2 { font-size: clamp(1.7rem, 3vw, 2.4rem); margin: 10px 0 16px; }
        .problem-box p { max-width: 46rem; line-height: 1.75; color: rgba(244,239,230,0.82); }
        .pills { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 22px; }
        .pill { border: 1px solid rgba(255,255,255,0.16); border-radius: 999px; padding: 7px 12px; font-size: 13px; }

        section { padding: 80px 0; }
        .center { text-align: center; }
        h2 { font-size: clamp(1.8rem, 3vw, 2.6rem); letter-spacing: -0.03em; margin-bottom: 12px; }
        .sub { color: var(--muted); max-width: 640px; margin: 0 auto 40px; line-height: 1.7; }

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
        .cta p { color: rgba(255,255,255,0.78); margin: 12px auto 24px; max-width: 520px; }

        footer { padding: 48px 0 28px; border-top: 1px solid var(--line); color: var(--muted); font-size: 14px; }
        .foot { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }

        @media (max-width: 900px) {
            .nav-links { display: none; }
            .hero-grid, .compare, .steps, .grid3, .price-grid { grid-template-columns: 1fr; }
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
            <li><a href="#problem">The problem</a></li>
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
            <div class="eyebrow sans">For Shopify stores selling to India</div>
            <h1>Shopify won’t let Indian shoppers pay in rupees. EaszyPay will.</h1>
            <p class="lede">Shopify Payments is not available in India, and native checkout stays in the store currency. EaszyPay adds a Buy Now button that opens a Stripe checkout in INR — then writes the paid order back to Shopify with coupons, shipping, and a thank-you page.</p>
            <div class="hero-actions sans">
                <a class="btn btn-solid" href="{{ route('tenant.register') }}">Add INR checkout</a>
                <a class="btn btn-ghost" href="#problem">Why Shopify blocks this</a>
            </div>
            <p class="fine sans">Works on Basic and above · Stripe India · Orders sync to Shopify admin</p>
        </div>
        <div class="card">
            <div class="compare sans">
                <article class="bad">
                    <h3>Shopify checkout</h3>
                    <p>No Shopify Payments in India. Customers are often charged in USD/EUR. High card declines. No local wallets that feel native.</p>
                </article>
                <article class="good">
                    <h3>EaszyPay checkout</h3>
                    <p>Prices convert to INR. Pay with Indian cards, Apple Pay, or Google Pay via Stripe. Order lands in Shopify as paid.</p>
                </article>
            </div>
            <div class="card" style="margin-top:12px;background:#0b1220;color:#f6f4ef;">
                <div class="sans" style="font-size:12px;opacity:.65;margin-bottom:8px;">Customer in Ghaziabad sees</div>
                <div style="font-size:28px;font-weight:800;">₹19,082.41</div>
                <div class="sans" style="font-size:13px;opacity:.7;margin-top:6px;">Converted from $200 store price · paid on Stripe · order #1042 in Shopify</div>
            </div>
        </div>
    </div>
</header>

<div class="problem" id="problem">
    <div class="wrap">
        <div class="problem-box">
            <div class="eyebrow sans" style="color:#99f6e4;">The gap</div>
            <h2>India is one of the largest Shopify audiences. Local currency still isn’t first-class.</h2>
            <p>Shopify Payments does not operate in India. Stores billed in USD (or any non-INR currency) send Indian buyers to a foreign-currency checkout. Banks decline more cards, UPI-style comfort is missing, and conversion drops. Markets &amp; currency apps help display prices — they do not give you a real INR charge on Shopify’s own checkout.</p>
            <p style="margin-top:14px;">EaszyPay is the workaround: keep your Shopify catalog, themes, discounts, and fulfillment. We only replace the payment step with Stripe in the shopper’s currency, then create the Shopify order as paid.</p>
            <div class="pills sans">
                <span class="pill">Shopify Payments not in India</span>
                <span class="pill">Foreign-currency declines</span>
                <span class="pill">Display currency ≠ charge currency</span>
                <span class="pill">EaszyPay charges INR on Stripe</span>
            </div>
        </div>
    </div>
</div>

<section id="how">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">How it works</div>
            <h2>Keep Shopify. Swap only the pay button.</h2>
            <p class="sub">No theme rewrite. Customers never leave a branded flow — they just pay in a currency their bank understands.</p>
        </div>
        <div class="steps sans">
            <div class="step"><div class="num">1</div><h3>Install the app</h3><p>Connect your .myshopify.com store. We inject a Buy Now button on product pages.</p></div>
            <div class="step"><div class="num">2</div><h3>Shopper pays in INR</h3><p>Location + FX convert the cart. Stripe India collects the rupee amount.</p></div>
            <div class="step"><div class="num">3</div><h3>Rules still apply</h3><p>Shopify discount codes, shipping profiles, and store policies run on our checkout.</p></div>
            <div class="step"><div class="num">4</div><h3>Order is native</h3><p>We create a paid Shopify order and show a thank-you page — no extra admin work.</p></div>
        </div>
    </div>
</section>

<section id="features" style="padding-top:0;">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">Product</div>
            <h2>Built for Indian demand on a global Shopify store.</h2>
        </div>
        <div class="grid3 sans">
            <div class="feat"><h3>INR (and 30+ currencies)</h3><p>Detect the buyer, convert from your store currency, charge on Stripe. Shopify still records the shop currency.</p></div>
            <div class="feat"><h3>Shopify coupons</h3><p>FESTIVE100 and other Discount API codes validate live — same percentage Shopify checkout would apply.</p></div>
            <div class="feat"><h3>Shipping from your settings</h3><p>Delivery profiles and zones (including $30 US / India rates) load into the custom checkout.</p></div>
            <div class="feat"><h3>Apple Pay &amp; Google Pay</h3><p>Shown only on the right device, through Stripe Express Checkout.</p></div>
            <div class="feat"><h3>Address autocomplete</h3><p>Street search fills city, state, and PIN so shipping can calculate like native checkout.</p></div>
            <div class="feat"><h3>Thank-you that stays</h3><p>Confirmation, addresses, and totals stay on EaszyPay — no bounce to a broken Shopify status URL.</p></div>
        </div>
    </div>
</section>

<section id="pricing">
    <div class="wrap">
        <div class="center">
            <div class="eyebrow sans">Pricing</div>
            <h2>Start on a trial. Scale when INR volume does.</h2>
        </div>
        <div class="price-grid sans">
            <div class="price">
                <div class="name">Starter</div>
                <div class="amt">$29</div>
                <div class="fine">/month + 1.5% per order</div>
                <ul>
                    <li>1 Shopify store</li>
                    <li>INR + multi-currency</li>
                    <li>Stripe wallets &amp; cards</li>
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
                <summary>Does Shopify support INR checkout in India?</summary>
                <p>Shopify Payments is not offered in India. You can show rupee prices with Markets, but the actual Shopify checkout often still cannot collect INR the way a local Stripe charge can. That is the gap EaszyPay fills.</p>
            </details>
            <details>
                <summary>Will my Shopify orders still look normal?</summary>
                <p>Yes. After Stripe succeeds we create a paid order in Shopify with line items, discount codes, shipping lines, and customer address — tagged as EaszyPay.</p>
            </details>
            <details>
                <summary>Do coupons and shipping still work?</summary>
                <p>Discount codes are validated against Shopify. Shipping methods come from your delivery profiles and zones, then get added to the Stripe total.</p>
            </details>
            <details>
                <summary>Do I need to leave Shopify Basic?</summary>
                <p>No. EaszyPay is built for stores that cannot turn on Shopify Payments and do not want to jump plans just to take Indian cards in INR.</p>
            </details>
        </div>
    </div>
</section>

<div class="wrap" style="padding-bottom:80px;">
    <div class="cta">
        <h2>Stop losing Indian orders to currency friction.</h2>
        <p>Install EaszyPay, keep your theme, and let buyers in India pay the way their bank expects.</p>
        <a class="btn btn-solid sans" href="{{ route('tenant.register') }}" style="background:#fff;color:#134e4a;">Create your EaszyPay account</a>
    </div>
</div>

<footer class="sans">
    <div class="wrap foot">
        <div><strong style="color:var(--ink);">EaszyPay</strong> · INR checkout for Shopify</div>
        <div>© {{ date('Y') }} EaszyPay. Not affiliated with Shopify Inc.</div>
    </div>
</footer>
</body>
</html>
