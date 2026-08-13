<?php
$errors_list = [];
if (isset($errors) && $errors->any()) {
    $errors_list = $errors->all();
}
$old_name = old('name', '');
$old_email = old('email', '');
$old_company = old('company_name', '');
$register_url = route('tenant.register.post');
$login_url = route('tenant.login');
$home_url = route('home');
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account — EaszyPay</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f6f4ef;
            color: #0b1220;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .wrap { width: 100%; max-width: 440px; }
        .brand {
            text-align: center;
            font-family: "Iowan Old Style", Palatino, Georgia, serif;
            font-size: 28px; font-weight: 800; letter-spacing: -0.03em;
            margin-bottom: 8px;
        }
        .brand span { color: #0f766e; }
        .sub { text-align: center; color: #5b6578; font-size: 14px; margin-bottom: 24px; line-height: 1.55; }
        .card {
            background: #fff;
            border: 1px solid #e6e9f0;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(15,23,42,0.06);
        }
        .trial {
            background: #e7f6f3; color: #134e4a; padding: 12px 16px; border-radius: 12px;
            text-align: center; font-size: 13px; font-weight: 650; margin-bottom: 20px; border: 1px solid #b7e0d8;
        }
        .err-box { background: #fdecea; border: 1px solid #f5c2c0; border-radius: 12px; padding: 12px 16px; color: #b42318; font-size: 13px; margin-bottom: 16px; }
        .fg { margin-bottom: 14px; }
        .fg label { display: block; font-size: 13px; font-weight: 650; color: #0b1220; margin-bottom: 6px; }
        .fg label .req { color: #b42318; }
        .fg input {
            width: 100%; height: 46px; padding: 0 14px; border: 1.5px solid #e6e9f0; border-radius: 12px;
            font-size: 15px; outline: none; font-family: inherit; background: #fff;
        }
        .fg input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.12); }
        .btn-submit {
            width: 100%; height: 48px; background: #0b1220; color: #fff; border: none; border-radius: 999px;
            font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px; font-family: inherit;
        }
        .btn-submit:hover { background: #134e4a; }
        .bottom-link { text-align: center; margin-top: 18px; font-size: 14px; color: #5b6578; }
        .bottom-link a { color: #0f766e; font-weight: 650; text-decoration: none; }
        .badges { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; margin-top: 18px; }
        .badge-sm { font-size: 11px; color: #5b6578; background: #faf8f3; border: 1px solid #e6e9f0; border-radius: 999px; padding: 4px 10px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand"><a href="<?php echo htmlspecialchars($home_url); ?>" style="color:inherit;text-decoration:none;">Easzy<span>Pay</span></a></div>
    <div class="sub">Keep Shopify. Charge the currency shoppers already accepted.</div>
    <div class="card">
        <div class="trial">14-day trial — no card required</div>
        <?php if (!empty($errors_list)): ?>
        <div class="err-box">
            <?php foreach ($errors_list as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($register_url); ?>">
            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="fg">
                <label>Full name <span class="req">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($old_name); ?>" placeholder="Your name" required autofocus>
            </div>
            <div class="fg">
                <label>Email <span class="req">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" placeholder="you@yourstore.com" required>
            </div>
            <div class="fg">
                <label>Company / store</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($old_company); ?>" placeholder="My Shopify store">
            </div>
            <div class="fg">
                <label>Password <span class="req">*</span></label>
                <input type="password" name="password" placeholder="Min 8 characters" required minlength="8">
            </div>
            <div class="fg">
                <label>Confirm password <span class="req">*</span></label>
                <input type="password" name="password_confirmation" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn-submit">Create free account</button>
        </form>
        <div class="bottom-link">
            Already have an account? <a href="<?php echo htmlspecialchars($login_url); ?>">Sign in</a>
        </div>
        <div class="badges">
            <span class="badge-sm">Stripe</span>
            <span class="badge-sm">Apple Pay</span>
            <span class="badge-sm">Google Pay</span>
            <span class="badge-sm">Local currency at pay</span>
        </div>
    </div>
</div>
</body>
</html>
