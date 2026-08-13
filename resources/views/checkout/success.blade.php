<?php
$rStatus = $redirectStatus ?? '';
$sess    = $session ?? null;
$pi      = $piId ?? '';
$oResult = $orderResult ?? null;

$cur = 'USD';
$amt = 0;
if ($sess) {
    $cur = $sess->charged_currency ?? $sess->currency ?? 'USD';
    $amt = $sess->charged_amount ?? $sess->subtotal ?? 0;
}

$symbols = ['USD'=>'$','GBP'=>'£','EUR'=>'€','INR'=>'₹','AUD'=>'A$','CAD'=>'CA$','AED'=>'AED ','SGD'=>'S$','JPY'=>'¥','NZD'=>'NZ$'];
$sym = $symbols[strtoupper($cur)] ?? ($cur.' ');

$redirectUrl = null;

if ($sess && !empty($sess->shopify_thank_you_url)) {
    $redirectUrl = $sess->shopify_thank_you_url;
} elseif ($oResult && !empty($oResult['thank_you_url'])) {
    $redirectUrl = $oResult['thank_you_url'];
} elseif ($oResult && !empty($oResult['order']['order_status_url'])) {
    $redirectUrl = $oResult['order']['order_status_url'];
} elseif ($sess && $sess->shopify_order_id && $sess->shop_domain) {
    $redirectUrl = "https://{$sess->shop_domain}/account/orders/{$sess->shopify_order_id}";
} elseif ($sess && $sess->shop_domain) {
    $redirectUrl = "https://{$sess->shop_domain}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thank You — Order Confirmed</title>

<?php if ($rStatus === 'succeeded' && $redirectUrl): ?>
<meta http-equiv="refresh" content="3;url=<?php echo htmlspecialchars($redirectUrl); ?>">
<?php endif; ?>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { min-height: 100%; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 23%),
                radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.16), transparent 20%),
                #050816;
    color: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.page-shell {
    width: 100%;
    max-width: 560px;
}
.card {
    background: rgba(15, 23, 42, 0.96);
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 28px;
    box-shadow: 0 24px 68px rgba(15, 23, 42, 0.35);
    overflow: hidden;
}
.card-header {
    padding: 40px 32px 26px;
    text-align: center;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(15, 23, 42, 0.88));
}
.icon {
    width: 96px;
    height: 96px;
    margin: 0 auto 22px;
    border-radius: 50%;
    background: linear-gradient(135deg, #34d399, #38bdf8);
    display: grid;
    place-items: center;
    font-size: 42px;
    color: #ffffff;
}
h1 {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.03em;
    margin-bottom: 12px;
}
.sub {
    color: #cbd5e1;
    font-size: 15px;
    line-height: 1.75;
    max-width: 460px;
    margin: 0 auto;
}
.card-body {
    padding: 26px 28px 34px;
}
.detail-box {
    background: rgba(148, 163, 184, 0.06);
    border: 1px solid rgba(148, 163, 184, 0.10);
    border-radius: 20px;
    padding: 22px 18px;
    margin-top: 24px;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    font-size: 14px;
}
.detail-row:last-child { border-bottom: none; }
.detail-row .label { color: #94a3b8; }
.detail-row .value { color: #fff; font-weight: 700; text-align: right; }
.value.amt { color: #34d399; font-size: 16px; }
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 16px;
    margin-top: 26px;
    border-radius: 18px;
    border: none;
    background: #f8fafc;
    color: #0f172a;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.02em;
    text-decoration: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18);
}
.redirect-note {
    margin-top: 24px;
    padding: 16px 18px;
    border-radius: 16px;
    background: rgba(59, 130, 246, 0.12);
    border: 1px solid rgba(148, 163, 184, 0.14);
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.7;
}
.countdown {
    color: #fff;
    font-weight: 700;
}
.note {
    margin-top: 18px;
    color: #94a3b8;
    font-size: 13px;
    line-height: 1.75;
}
</style>
</head>
<body>
<div class="page-shell">
<?php if ($rStatus === 'succeeded'): ?>
    <div class="card">
        <div class="card-header">
            <div class="icon">✓</div>
            <h1>Order confirmed</h1>
            <?php if ($sess && $sess->shopify_order_number): ?>
                <p class="sub">Order <strong><?php echo htmlspecialchars($sess->shopify_order_number); ?></strong> has been placed successfully. Redirecting you to the Shopify order page.</p>
            <?php else: ?>
                <p class="sub">Your payment was successful. We’re finalizing your order and creating your Shopify confirmation.</p>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="detail-box">
                <?php if ($amt > 0): ?>
                    <div class="detail-row">
                        <span class="label">Amount paid</span>
                        <span class="value amt"><?php echo $sym . number_format($amt / 100, 2); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($sess && $sess->shopify_order_number): ?>
                    <div class="detail-row">
                        <span class="label">Order number</span>
                        <span class="value"><?php echo htmlspecialchars($sess->shopify_order_number); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($sess && $sess->customer_email): ?>
                    <div class="detail-row">
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($sess->customer_email); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($sess && $sess->card_brand && $sess->card_last4): ?>
                    <div class="detail-row">
                        <span class="label">Payment method</span>
                        <span class="value"><?php echo strtoupper($sess->card_brand); ?> •••• <?php echo htmlspecialchars($sess->card_last4); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($redirectUrl): ?>
                <div class="redirect-note">
                    Redirecting you to your order page in <span class="countdown" id="cd">3</span> seconds.
                </div>
                <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="btn">View order</a>
            <?php elseif ($sess && $sess->shop_domain): ?>
                <a href="https://<?php echo htmlspecialchars($sess->shop_domain); ?>" class="btn">Continue shopping</a>
            <?php endif; ?>

            <p class="note">If the page does not redirect automatically, use the button above. Please keep this window open while your order is confirmed.</p>
        </div>
    </div>

    <?php if ($redirectUrl): ?>
        <script>
        (function() {
            var url = '<?php echo addslashes($redirectUrl); ?>';
            var counter = 3;
            var el = document.getElementById('cd');

            var timer = setInterval(function() {
                counter -= 1;
                if (el) el.textContent = counter > 0 ? counter : '0';
                if (counter <= 0) {
                    clearInterval(timer);
                    window.location.href = url;
                }
            }, 1000);

            setTimeout(function() {
                window.location.href = url;
            }, 3500);
        })();
        </script>
    <?php endif; ?>

<?php elseif ($rStatus === 'failed' || $rStatus === 'canceled'): ?>
    <div class="card">
        <div class="card-header">
            <div class="icon" style="background:#ef4444;">✕</div>
            <h1>Payment failed</h1>
            <p class="sub">Your payment could not be processed. No charges were made.</p>
        </div>
        <div class="card-body">
            <a href="javascript:history.back()" class="btn">Try again</a>
        </div>
    </div>

<?php else: ?>
    <div class="card">
        <div class="card-header">
            <div class="icon" style="background:#f59e0b;">⏳</div>
            <h1>Processing</h1>
            <p class="sub">Please wait while we confirm your payment. This page will refresh shortly.</p>
        </div>
    </div>
    <script>setTimeout(function(){ location.reload(); }, 3000);</script>
<?php endif; ?>
</div>
</body>
</html>