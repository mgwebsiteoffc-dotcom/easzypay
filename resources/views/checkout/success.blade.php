<?php
$rStatus = $redirectStatus ?? '';
$sess    = $session ?? null;
$oResult = $orderResult ?? null;

$cur = 'USD';
$amt = 0;
$sub = 0;
$ship = 0;
$disc = 0;
$items = [];
$shopName = 'Store';
$shopUrl = '';
$logoUrl = null;

if ($sess) {
    $cur  = strtoupper($sess->charged_currency ?? $sess->currency ?? 'USD');
    $amt  = (int) ($sess->charged_amount ?? $sess->total_amount ?? $sess->subtotal ?? 0);
    $sub  = (int) ($sess->subtotal ?? 0);
    $ship = (int) ($sess->shipping_amount ?? 0);
    $disc = (int) ($sess->discount_amount ?? 0);
    $items = is_array($sess->items) ? $sess->items : [];
    if ($sess->store_id) {
        $store = \App\Models\Store::find($sess->store_id);
        $shopName = $store->shop_name ?? $shopName;
        $logoUrl = $store->checkout_logo ?? null;
    }
    $shopUrl = $sess->shop_domain ? ('https://' . $sess->shop_domain) : '';
}

$rate = (float) ($sess->exchange_rate ?? 1);
if ($rate <= 0) $rate = 1;
$disp = function (int $cents) use ($cur, $rate, $sess) {
    $symbols = ['USD'=>'$','GBP'=>'£','EUR'=>'€','INR'=>'₹','AUD'=>'A$','CAD'=>'CA$','AED'=>'AED ','SGD'=>'S$','JPY'=>'¥','NZD'=>'NZ$'];
    $sym = $symbols[$cur] ?? ($cur . ' ');
    $val = $cents;
    $shopCur = strtoupper($sess->currency ?? $cur);
    if ($shopCur !== $cur && $rate != 1.0) {
        $val = (int) round($cents * $rate);
    }
    return $sym . number_format($val / 100, 2);
};

$first = trim((string) ($sess->customer_first_name ?? ''));
$last  = trim((string) ($sess->customer_last_name ?? ''));
$name  = trim($first . ' ' . $last) ?: 'there';
$email = trim((string) ($sess->customer_email ?? ''));
$phone = trim((string) ($sess->customer_phone ?? ''));
$orderNo = $sess->shopify_order_number ?? null;
$shipTitle = trim((string) ($sess->referrer ?? '')) ?: 'Standard Shipping';
$addr = array_filter([
    $sess->shipping_address1 ?? '',
    $sess->shipping_address2 ?? '',
    trim(($sess->shipping_city ?? '') . (($sess->shipping_state ?? '') ? ', ' . $sess->shipping_state : '') . ' ' . ($sess->shipping_zip ?? '')),
    $sess->shipping_country ?? '',
]);
$payLabel = '';
if ($sess && $sess->card_brand && $sess->card_last4) {
    $payLabel = strtoupper($sess->card_brand) . ' ending with ' . $sess->card_last4;
} elseif ($sess && $sess->wallet_type && $sess->wallet_type !== 'card') {
    $payLabel = ucfirst($sess->wallet_type);
} else {
    $payLabel = 'Card';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thank you<?php echo $orderNo ? ' — ' . htmlspecialchars($orderNo) : ''; ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #f5f5f5;
    color: #1a1a1a;
    min-height: 100vh;
}
.hdr {
    background: #fff;
    border-bottom: 1px solid #e6e6e6;
    padding: 16px 24px;
}
.hdr-inner {
    max-width: 1100px;
    margin: 0 auto;
    font-weight: 700;
    font-size: 18px;
}
.hdr-inner img { max-height: 32px; }
.wrap {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
    min-height: calc(100vh - 64px);
}
@media (max-width: 860px) { .wrap { grid-template-columns: 1fr; } }
.left { padding: 40px 40px 64px; }
.right {
    background: #eee;
    padding: 40px 32px 64px;
    border-left: 1px solid #e0e0e0;
}
@media (max-width: 860px) {
    .left, .right { padding: 24px; }
    .right { border-left: none; border-top: 1px solid #e0e0e0; }
}
.thanks {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 28px;
}
.check {
    width: 48px; height: 48px; flex-shrink: 0;
    border: 2px solid #1a1a1a; border-radius: 50%;
    display: grid; place-items: center; font-size: 22px;
}
h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.03em; }
.muted { color: #616161; font-size: 14px; line-height: 1.6; margin-top: 4px; }
.panel {
    background: #fff;
    border: 1px solid #e6e6e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
}
.panel h2 { font-size: 16px; margin-bottom: 12px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 640px) { .grid2 { grid-template-columns: 1fr; } }
.k { color: #616161; font-size: 13px; margin-bottom: 4px; }
.v { font-size: 14px; line-height: 1.5; }
.btn {
    display: inline-flex; align-items: center; justify-content: center;
    background: #1a1a1a; color: #fff; text-decoration: none;
    padding: 14px 22px; border-radius: 8px; font-weight: 650; font-size: 14px;
}
.item { display: flex; gap: 12px; margin-bottom: 16px; align-items: flex-start; }
.thumb {
    width: 64px; height: 64px; border-radius: 8px; object-fit: cover;
    background: #fff; border: 1px solid #ddd; position: relative;
}
.qty {
    position: absolute; top: -8px; right: -8px;
    background: #616161; color: #fff; border-radius: 50%;
    min-width: 20px; height: 20px; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.iname { font-size: 14px; font-weight: 600; }
.ivar { color: #616161; font-size: 13px; }
.iprice { margin-left: auto; font-size: 14px; white-space: nowrap; }
.row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
.row.total { font-size: 20px; font-weight: 700; padding-top: 12px; border-top: 1px solid #ddd; margin-top: 8px; }
.cur { color: #757575; font-size: 12px; font-weight: 500; margin-right: 6px; }
.wait { color: #616161; font-size: 14px; }
</style>
</head>
<body>
<header class="hdr">
    <div class="hdr-inner">
        <?php if ($logoUrl): ?>
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($shopName); ?>">
        <?php else: ?>
            <?php echo htmlspecialchars($shopName); ?>
        <?php endif; ?>
    </div>
</header>

<?php if ($rStatus === 'succeeded'): ?>
<div class="wrap">
    <div class="left">
        <div class="thanks">
            <div class="check">✓</div>
            <div>
                <?php if ($orderNo): ?>
                    <div class="muted">Order <?php echo htmlspecialchars($orderNo); ?></div>
                <?php endif; ?>
                <h1>Thank you, <?php echo htmlspecialchars($first ?: $name); ?>!</h1>
                <p class="muted">
                    <?php if ($email): ?>
                        Your order is confirmed. You’ll receive an email confirmation at <strong><?php echo htmlspecialchars($email); ?></strong>.
                    <?php else: ?>
                        Your order is confirmed. A receipt has been sent if an email was provided.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="panel">
            <h2>Order details</h2>
            <div class="grid2">
                <div>
                    <div class="k">Contact information</div>
                    <div class="v">
                        <?php echo htmlspecialchars($email ?: '—'); ?><br>
                        <?php if ($phone) echo htmlspecialchars($phone); ?>
                    </div>
                </div>
                <div>
                    <div class="k">Payment method</div>
                    <div class="v"><?php echo htmlspecialchars($payLabel); ?> — <?php echo $disp($amt > 0 ? ($cur === strtoupper($sess->currency ?? $cur) ? ($sub - $disc + $ship) : $amt) : $amt); ?></div>
                </div>
                <div>
                    <div class="k">Shipping address</div>
                    <div class="v">
                        <?php echo htmlspecialchars($name); ?><br>
                        <?php foreach ($addr as $line): ?>
                            <?php echo htmlspecialchars($line); ?><br>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <div class="k">Shipping method</div>
                    <div class="v"><?php echo htmlspecialchars($shipTitle); ?></div>
                    <div class="k" style="margin-top:14px;">Billing address</div>
                    <div class="v">Same as shipping address</div>
                </div>
            </div>
        </div>

        <?php if ($shopUrl): ?>
            <a class="btn" href="<?php echo htmlspecialchars($shopUrl); ?>">Continue shopping</a>
        <?php endif; ?>
    </div>

    <aside class="right">
        <?php foreach ($items as $item):
            $pr = (int) ($item['price'] ?? $item['price_cents'] ?? 0);
            $qt = max(1, (int) ($item['quantity'] ?? 1));
            $vt = trim($item['variant_title'] ?? '');
        ?>
        <div class="item">
            <div style="position:relative;flex-shrink:0;">
                <?php if (!empty($item['image'])): ?>
                    <img class="thumb" src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                <?php else: ?>
                    <div class="thumb"></div>
                <?php endif; ?>
                <span class="qty"><?php echo $qt; ?></span>
            </div>
            <div>
                <div class="iname"><?php echo htmlspecialchars($item['title'] ?? 'Product'); ?></div>
                <?php if ($vt && strtolower($vt) !== 'default title'): ?>
                    <div class="ivar"><?php echo htmlspecialchars($vt); ?></div>
                <?php endif; ?>
            </div>
            <div class="iprice"><?php echo $disp($pr * $qt); ?></div>
        </div>
        <?php endforeach; ?>

        <div class="row"><span>Subtotal</span><span><?php echo $disp($sub); ?></span></div>
        <?php if ($disc > 0): ?>
            <div class="row"><span>Discount<?php echo $sess->discount_code ? ' (' . htmlspecialchars($sess->discount_code) . ')' : ''; ?></span><span>−<?php echo $disp($disc); ?></span></div>
        <?php endif; ?>
        <div class="row"><span>Shipping</span><span><?php echo $ship > 0 ? $disp($ship) : 'Free'; ?></span></div>
        <div class="row total">
            <span>Total</span>
            <span><span class="cur"><?php echo htmlspecialchars($cur); ?></span><?php
                $shown = $amt;
                if ($shown <= 0) $shown = max(0, $sub - $disc + $ship);
                echo $disp($cur === strtoupper((string) ($sess->currency ?? $cur)) ? max(0, $sub - $disc + $ship) : $shown);
            ?></span>
        </div>
    </aside>
</div>

<?php elseif ($rStatus === 'failed' || $rStatus === 'canceled'): ?>
<div class="left" style="max-width:640px;margin:60px auto;">
    <div class="thanks">
        <div class="check" style="border-color:#d72c0d;color:#d72c0d;">✕</div>
        <div>
            <h1>Payment failed</h1>
            <p class="muted">Your payment could not be processed. No charges were made.</p>
            <a class="btn" href="javascript:history.back()" style="margin-top:20px;">Try again</a>
        </div>
    </div>
</div>

<?php else: ?>
<div class="left" style="max-width:640px;margin:60px auto;">
    <h1>Processing</h1>
    <p class="wait">Please wait while we confirm your payment…</p>
</div>
<script>setTimeout(function(){ location.reload(); }, 2500);</script>
<?php endif; ?>
</body>
</html>
